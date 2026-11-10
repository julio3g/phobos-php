<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;
use Phobos\Enums\OrderStatus;
use Phobos\Enums\PaymentStatus;
use Phobos\Traits\HasTimestamps;

/**
 * Ordem de serviço. ASSOCIA-SE a um Client e COMPÕE seus OrderItem.
 * Subtotal, total e lucro são derivados dos itens; o status segue a
 * máquina de estados definida em OrderStatus.
 */
final class Order extends Entity
{
    use HasTimestamps;

    /** @var list<OrderItem> */
    private array $items = [];
    private int $number = 0;
    private OrderStatus $status = OrderStatus::Draft;
    private PaymentStatus $paymentStatus = PaymentStatus::Pending;
    private float $discount = 0.0;
    private float $shipping = 0.0;
    private float $paidAmount = 0.0;
    private ?string $notes = null;

    public function __construct(private Client $client)
    {
    }

    /**
     * @param array<string,mixed> $row
     * @param list<OrderItem>     $items
     */
    public static function fromRow(array $row, Client $client, array $items): self
    {
        $o = new self($client);
        $o->id = (int) $row['id'];
        $o->number = (int) $row['number'];
        $o->status = OrderStatus::from((string) $row['status']);
        $o->paymentStatus = PaymentStatus::from((string) $row['payment_status']);
        $o->discount = (float) $row['discount'];
        $o->shipping = (float) $row['shipping'];
        $o->paidAmount = (float) $row['paid_amount'];
        $o->notes = $row['notes'] ?? null;
        $o->items = $items;
        $o->hydrateTimestamps($row);

        return $o;
    }

    public function label(): string
    {
        return sprintf('#%04d', $this->number);
    }

    public function number(): int
    {
        return $this->number;
    }

    public function assignNumber(int $number): void
    {
        $this->number = $number;
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function paymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function isEditable(): bool
    {
        return $this->status === OrderStatus::Draft;
    }

    /** @return list<OrderItem> */
    public function items(): array
    {
        return $this->items;
    }

    public function addItem(Product $product, float $quantity): void
    {
        $this->assertEditable();
        foreach ($this->items as $item) {
            if ($item->productId() === $product->id()) {
                throw new DomainException("O produto \"{$product->label()}\" foi informado mais de uma vez.");
            }
        }
        $this->items[] = OrderItem::fromProduct($product, $quantity);
    }

    public function discount(): float
    {
        return $this->discount;
    }

    public function shipping(): float
    {
        return $this->shipping;
    }

    public function setAdjustments(float $discount, float $shipping): void
    {
        $this->assertEditable();
        if ($discount < 0 || $shipping < 0) {
            throw new DomainException('Desconto e frete não podem ser negativos.');
        }
        if ($discount > $this->subtotal()) {
            throw new DomainException('O desconto não pode ser maior que o subtotal.');
        }
        $this->discount = $discount;
        $this->shipping = $shipping;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = self::nullableText($notes, 'Observações', 150);
    }

    public function subtotal(): float
    {
        return round(array_sum(array_map(static fn (OrderItem $i) => $i->total(), $this->items)), 2);
    }

    public function costTotal(): float
    {
        return round(array_sum(array_map(static fn (OrderItem $i) => $i->costTotal(), $this->items)), 2);
    }

    public function total(): float
    {
        return round($this->subtotal() - $this->discount + $this->shipping, 2);
    }

    public function profit(): float
    {
        return round($this->total() - $this->costTotal(), 2);
    }

    public function paidAmount(): float
    {
        return $this->paidAmount;
    }

    public function changeStatus(OrderStatus $target): void
    {
        if (!$this->status->canTransitionTo($target)) {
            throw new DomainException("Não é possível mudar de \"{$this->status->label()}\" para \"{$target->label()}\".");
        }
        if ($target === OrderStatus::Confirmed && $this->items === []) {
            throw new DomainException('Adicione ao menos um item antes de confirmar.');
        }
        $this->status = $target;
    }

    public function registerPayment(PaymentStatus $status, float $paidAmount): void
    {
        if ($paidAmount < 0 || $paidAmount > $this->total()) {
            throw new DomainException('O valor pago deve estar entre zero e o total da ordem.');
        }
        $this->paymentStatus = $status;
        $this->paidAmount = $paidAmount;
    }

    private function assertEditable(): void
    {
        if (!$this->isEditable()) {
            throw new DomainException('Somente ordens em rascunho podem ter itens e valores alterados.');
        }
    }
}
