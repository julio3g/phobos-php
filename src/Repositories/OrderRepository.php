<?php

declare(strict_types=1);

namespace Phobos\Repositories;

use DomainException;
use Phobos\Models\Client;
use Phobos\Models\Entity;
use Phobos\Models\Order;
use Phobos\Models\OrderItem;

final class OrderRepository extends AbstractRepository
{
    protected function table(): string
    {
        return 'orders';
    }

    protected function hydrate(array $row): Order
    {
        $clientRow = $this->run('SELECT * FROM clients WHERE id = :id', ['id' => $row['client_id']])->fetch();
        if ($clientRow === false) {
            throw new DomainException('Cliente da ordem não encontrado.');
        }

        return Order::fromRow($row, Client::fromRow($clientRow), $this->loadItems((int) $row['id']));
    }

    /** @return list<OrderItem> */
    private function loadItems(int $orderId): array
    {
        $rows = $this->run(
            'SELECT oi.*, p.name AS product_name, p.variant AS product_variant
               FROM order_items oi JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = :id ORDER BY oi.id',
            ['id' => $orderId],
        )->fetchAll();

        return array_map(
            static fn (array $r) => new OrderItem(
                (int) $r['product_id'],
                implode(' · ', array_filter([$r['product_name'], $r['product_variant']])),
                (float) $r['quantity'],
                (float) $r['unit_price'],
                (float) $r['unit_cost'],
            ),
            $rows,
        );
    }

    /**
     * Modelo de leitura para a listagem (1 consulta, sem N+1).
     *
     * @return list<array<string,mixed>>
     */
    public function summaries(string $status = ''): array
    {
        return $this->run(
            'SELECT o.id, o.number, o.status, o.payment_status, o.total, o.created_at,
                    COALESCE(NULLIF(c.trade_name, \'\'), c.name) AS client_name
               FROM orders o JOIN clients c ON c.id = o.client_id
              WHERE (:s = \'\' OR o.status = :s)
              ORDER BY o.number DESC',
            ['s' => $status],
        )->fetchAll();
    }

    /** Salva ordem + itens em uma transação; o número sequencial é reservado com lock. */
    public function save(Entity $entity): Entity
    {
        /** @var Order $entity */
        $this->transaction(function () use ($entity): void {
            $params = [
                'client' => $entity->client()->id(),
                'status' => $entity->status()->value,
                'pstatus' => $entity->paymentStatus()->value,
                'subtotal' => $entity->subtotal(),
                'discount' => $entity->discount(),
                'shipping' => $entity->shipping(),
                'cost' => $entity->costTotal(),
                'paid' => $entity->paidAmount(),
                'notes' => $entity->notes(),
            ];

            if ($entity->id() === null) {
                // serializa a geração do número entre requisições concorrentes
                $this->pdo->exec('SELECT pg_advisory_xact_lock(7001)');
                $number = (int) $this->pdo->query('SELECT COALESCE(MAX(number), 0) + 1 FROM orders')->fetchColumn();
                $entity->assignNumber($number);
                $id = $this->run(
                    'INSERT INTO orders (number, client_id, status, payment_status, subtotal, discount, shipping, cost_total, paid_amount, notes)
                     VALUES (:number, :client, :status, :pstatus, :subtotal, :discount, :shipping, :cost, :paid, :notes) RETURNING id',
                    $params + ['number' => $number],
                )->fetchColumn();
                $entity->assignId((int) $id);
            } else {
                $this->run(
                    'UPDATE orders SET client_id = :client, status = :status, payment_status = :pstatus, subtotal = :subtotal,
                            discount = :discount, shipping = :shipping, cost_total = :cost, paid_amount = :paid, notes = :notes,
                            updated_at = now()
                      WHERE id = :id',
                    $params + ['id' => $entity->id()],
                );
                $this->run('DELETE FROM order_items WHERE order_id = :id', ['id' => $entity->id()]);
            }

            foreach ($entity->items() as $item) {
                $this->run(
                    'INSERT INTO order_items (order_id, product_id, quantity, unit_price, unit_cost, total)
                     VALUES (:o, :p, :q, :up, :uc, :t)',
                    [
                        'o' => $entity->id(),
                        'p' => $item->productId(),
                        'q' => $item->quantity(),
                        'up' => $item->unitPrice(),
                        'uc' => $item->unitCost(),
                        't' => $item->total(),
                    ],
                );
            }
        });

        return $entity;
    }

    /** @return array{revenue:float,profit:float,open:int,total:int} */
    public function stats(): array
    {
        $row = $this->run(
            "SELECT COALESCE(SUM(total) FILTER (WHERE status <> 'cancelled' AND status <> 'draft'), 0) AS revenue,
                    COALESCE(SUM(total - cost_total) FILTER (WHERE status <> 'cancelled' AND status <> 'draft'), 0) AS profit,
                    COUNT(*) FILTER (WHERE status IN ('draft','confirmed','in_production')) AS open,
                    COUNT(*) AS total
               FROM orders",
        )->fetch();

        return ['revenue' => (float) $row['revenue'], 'profit' => (float) $row['profit'], 'open' => (int) $row['open'], 'total' => (int) $row['total']];
    }
}
