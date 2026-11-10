<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use DomainException;
use Phobos\Core\Validator;
use Phobos\Enums\OrderStatus;
use Phobos\Enums\PaymentStatus;
use Phobos\Models\Order;
use Phobos\Repositories\ClientRepository;
use Phobos\Repositories\OrderRepository;
use Phobos\Repositories\ProductRepository;

final class OrderController extends Controller
{
    private OrderRepository $orders;
    private ClientRepository $clients;
    private ProductRepository $products;

    public function __construct()
    {
        parent::__construct();
        $this->orders = new OrderRepository($this->pdo);
        $this->clients = new ClientRepository($this->pdo);
        $this->products = new ProductRepository($this->pdo);
    }

    public function index(): void
    {
        $status = OrderStatus::tryFrom((string) ($_GET['status'] ?? ''))?->value ?? '';
        $this->view('orders/index', ['orders' => $this->orders->summaries($status), 'status' => $status]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->form(null, ['lines' => []], []);
    }

    public function show(string $id): void
    {
        $this->view('orders/show', ['order' => $this->findOrFail($id)]);
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $o = $this->findOrFail($id);
        if (!$o->isEditable()) {
            $this->flash('error', 'Somente ordens em rascunho podem ser editadas.');
            $this->redirect('/orders/' . $o->id());
        }
        $lines = array_map(static fn ($i) => ['id' => $i->productId(), 'qty' => $i->quantity()], $o->items());
        $this->form($o, [
            'client_id' => $o->client()->id(), 'discount' => $o->discount(), 'shipping' => $o->shipping(),
            'notes' => $o->notes(), 'lines' => $lines,
        ], []);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $this->persist(null);
    }

    public function update(string $id): void
    {
        $this->requireAdmin();
        $this->persist($this->findOrFail($id));
    }

    public function changeStatus(string $id): void
    {
        $this->requireAdmin();
        $o = $this->findOrFail($id);
        try {
            $target = OrderStatus::tryFrom($this->post('status')) ?? throw new DomainException('Status inválido.');
            $o->changeStatus($target);
            $this->orders->save($o);
            $this->flash('success', 'Status alterado para ' . $target->label() . '.');
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('/orders/' . $o->id());
    }

    public function registerPayment(string $id): void
    {
        $this->requireAdmin();
        $o = $this->findOrFail($id);
        try {
            $status = PaymentStatus::tryFrom($this->post('payment_status')) ?? throw new DomainException('Situação de pagamento inválida.');
            $o->registerPayment($status, $this->postFloat('paid_amount'));
            $this->orders->save($o);
            $this->flash('success', 'Pagamento atualizado.');
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
        }
        $this->redirect('/orders/' . $o->id());
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $o = $this->findOrFail($id);
        if ($o->status() !== OrderStatus::Draft && $o->status() !== OrderStatus::Cancelled) {
            $this->flash('error', 'Só é possível excluir ordens em rascunho ou canceladas.');
            $this->redirect('/orders/' . $o->id());
        }
        $this->orders->delete((int) $o->id());
        $this->flash('success', 'Ordem excluída.');
        $this->redirect('/orders');
    }

    /** @param array<string,mixed> $form @param array<string,string> $errors */
    private function form(?Order $order, array $form, array $errors, int $status = 200): void
    {
        $this->view('orders/form', [
            'order' => $order, 'form' => $form, 'errors' => $errors,
            'clients' => $this->clients->search(), 'products' => $this->products->active(),
        ], $status);
    }

    private function persist(?Order $existing): void
    {
        $form = $_POST;
        $form['lines'] = $this->postedLines();

        $v = (new Validator($form))
            ->required('client_id', 'Cliente')
            ->number('discount', 'Desconto', 0)->number('shipping', 'Frete', 0)
            ->maxLength('notes', 'Observações', 150);

        try {
            if ($v->fails()) {
                throw new DomainException('Corrija os campos destacados.');
            }
            if ($form['lines'] === []) {
                throw new DomainException('Adicione ao menos um item.');
            }
            $client = $this->clients->find($this->intId($this->post('client_id'))) ?? throw new DomainException('Cliente não encontrado.');

            // preço e custo vêm sempre do cadastro (nunca do formulário)
            $order = new Order($client);
            foreach ($form['lines'] as $line) {
                $product = $this->products->find($line['id']) ?? throw new DomainException('Produto não encontrado.');
                $order->addItem($product, $line['qty']);
            }
            $order->setAdjustments($this->postFloat('discount'), $this->postFloat('shipping'));
            $order->setNotes($this->postNullable('notes'));

            if ($existing !== null) {
                if (!$existing->isEditable()) {
                    throw new DomainException('Somente ordens em rascunho podem ser editadas.');
                }
                $order->assignId((int) $existing->id());
                $order->assignNumber($existing->number());
            }
            $this->orders->save($order);

            $this->flash('success', $existing ? 'Ordem atualizada.' : 'Ordem criada.');
            $this->redirect('/orders/' . $order->id());
        } catch (DomainException $e) {
            $v->add('_form', $e->getMessage());
            $this->form($existing, $form, $v->errors(), 422);
        }
    }

    /** @return list<array{id:int,qty:float}> */
    private function postedLines(): array
    {
        $ids = (array) ($_POST['product_id'] ?? []);
        $qtys = (array) ($_POST['product_qty'] ?? []);
        $lines = [];
        foreach ($ids as $i => $id) {
            $qty = Validator::toFloat((string) ($qtys[$i] ?? ''));
            if (is_string($id) && ctype_digit($id) && $qty !== null && $qty > 0) {
                $lines[] = ['id' => (int) $id, 'qty' => $qty];
            }
        }

        return $lines;
    }

    private function findOrFail(string $id): Order
    {
        $o = $this->orders->find($this->intId($id));

        return $o instanceof Order ? $o : $this->notFound();
    }
}
