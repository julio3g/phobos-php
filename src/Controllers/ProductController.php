<?php

declare(strict_types=1);

namespace Phobos\Controllers;

use DomainException;
use Phobos\Core\Validator;
use Phobos\Enums\MarginType;
use Phobos\Models\Product;
use Phobos\Pricing\PricingStrategyFactory;
use Phobos\Repositories\MaterialRepository;
use Phobos\Repositories\ProductRepository;
use Phobos\Support\ImageUploader;

final class ProductController extends Controller
{
    private ProductRepository $products;
    private MaterialRepository $materials;

    public function __construct()
    {
        parent::__construct();
        $this->products = new ProductRepository($this->pdo);
        $this->materials = new MaterialRepository($this->pdo);
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $archived = ($_GET['archived'] ?? '') === '1';
        $this->view('products/index', ['products' => $this->products->search($q, $archived), 'q' => $q, 'archived' => $archived]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $this->form(null, ['margin_type' => 'percent', 'lines' => []], []);
    }

    public function show(string $id): void
    {
        $this->view('products/show', ['product' => $this->findOrFail($id)]);
    }

    public function edit(string $id): void
    {
        $this->requireAdmin();
        $p = $this->findOrFail($id);
        $lines = array_map(static fn ($l) => ['id' => $l->material()->id(), 'qty' => $l->quantity()], $p->lines());
        $this->form($p, [
            'name' => $p->name(), 'variant' => $p->variant(), 'description' => $p->description(),
            'hourly_rate' => $p->hourlyRate(), 'labor_minutes' => $p->laborMinutes(),
            'margin_type' => $p->marginType()->value, 'margin_value' => $p->marginValue(), 'lines' => $lines,
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

    public function recalculate(string $id): void
    {
        $this->requireAdmin();
        $p = $this->findOrFail($id);
        $p->refreshCosts();
        $this->products->save($p);
        $this->flash('success', 'Custos recalculados com os preços atuais dos insumos.');
        $this->redirect('/products/' . $p->id());
    }

    public function archive(string $id): void
    {
        $this->requireAdmin();
        $p = $this->findOrFail($id);
        $p->isArchived() ? $p->restore() : $p->archive();
        $this->products->save($p);
        $this->flash('success', $p->isArchived() ? 'Produto arquivado.' : 'Produto restaurado.');
        $this->redirect('/products');
    }

    public function destroy(string $id): void
    {
        $this->requireAdmin();
        $p = $this->findOrFail($id);
        try {
            $this->products->delete((int) $p->id());
            $this->flash('success', 'Produto excluído.');
        } catch (DomainException) {
            $this->flash('error', 'Produto usado em ordens; arquive em vez de excluir.');
        }
        $this->redirect('/products');
    }

    /** @param array<string,mixed> $form @param array<string,string> $errors */
    private function form(?Product $product, array $form, array $errors, int $status = 200): void
    {
        $this->view('products/form', [
            'product' => $product, 'form' => $form, 'errors' => $errors,
            'materialOptions' => $this->materials->active(),
        ], $status);
    }

    private function persist(?Product $existing): void
    {
        $form = $_POST;
        $form['lines'] = $this->postedLines();
        $type = MarginType::tryFrom($this->post('margin_type')) ?? MarginType::Percent;

        $v = (new Validator($form))
            ->required('name', 'Nome')->maxLength('name', 'Nome', 150)
            ->number('hourly_rate', 'Valor/hora', 0)->number('labor_minutes', 'Minutos', 0)
            ->required('margin_value', 'Margem')->number('margin_value', 'Margem', 0)
            ->in('margin_type', 'Tipo de margem', array_map(static fn (MarginType $t) => $t->value, MarginType::cases()));

        try {
            if ($v->fails()) {
                throw new DomainException('Corrija os campos destacados.');
            }
            $product = $existing ?? new Product($this->post('name'), 0, 0, MarginType::Percent, 0);
            $product->setName($this->post('name'));
            $product->setDetails($this->postNullable('variant'), $this->postNullable('description'));
            $product->setLabor($this->postFloat('hourly_rate'), (int) $this->postFloat('labor_minutes'));
            $product->setMargin($type, $this->postFloat('margin_value'));

            // recompõe a composição a partir do formulário (snapshot do custo atual)
            $product->clearMaterials();
            $found = $this->materials->findManyById(array_column($form['lines'], 'id'));
            foreach ($form['lines'] as $line) {
                $material = $found[$line['id']] ?? throw new DomainException('Insumo não encontrado.');
                $product->addMaterial($material, $line['qty']);
            }
            $product->recalculate();

            $url = (new ImageUploader(dirname(__DIR__, 2) . '/public/uploads'))->store($_FILES['image'] ?? null, 'products');
            if ($url !== null) {
                $product->setImageUrl($url);
            } elseif ($this->post('remove_image') === '1') {
                $product->setImageUrl(null);
            }

            $this->products->save($product);
            $this->flash('success', $existing ? 'Produto atualizado.' : 'Produto criado.');
            $this->redirect('/products/' . $product->id());
        } catch (DomainException $e) {
            $v->add('_form', $e->getMessage());
            $this->form($existing, $form, $v->errors(), 422);
        }
    }

    /** @return list<array{id:int,qty:float}> */
    private function postedLines(): array
    {
        $ids = (array) ($_POST['material_id'] ?? []);
        $qtys = (array) ($_POST['material_qty'] ?? []);
        $lines = [];
        foreach ($ids as $i => $id) {
            $qty = Validator::toFloat((string) ($qtys[$i] ?? ''));
            if (is_string($id) && ctype_digit($id) && $qty !== null && $qty > 0) {
                $lines[] = ['id' => (int) $id, 'qty' => $qty];
            }
        }

        return $lines;
    }

    private function findOrFail(string $id): Product
    {
        $p = $this->products->find($this->intId($id));

        return $p instanceof Product ? $p : $this->notFound();
    }
}
