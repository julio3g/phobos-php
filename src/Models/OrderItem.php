<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;

/**
 * Item da ordem (COMPOSIÇÃO: não existe fora da Order). Preço e custo são
 * copiados do Product no momento da inclusão e não mudam depois.
 */
final class OrderItem
{
    public function __construct(
        private readonly int $productId,
        private readonly string $productName,
        private readonly float $quantity,
        private readonly float $unitPrice,
        private readonly float $unitCost,
    ) {
        if ($quantity <= 0) {
            throw new DomainException('A quantidade do item deve ser maior que zero.');
        }
    }

    public static function fromProduct(Product $product, float $quantity): self
    {
        return new self((int) $product->id(), $product->label(), $quantity, $product->salePrice(), $product->totalCost());
    }

    public function productId(): int
    {
        return $this->productId;
    }

    public function productName(): string
    {
        return $this->productName;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function unitPrice(): float
    {
        return $this->unitPrice;
    }

    public function unitCost(): float
    {
        return $this->unitCost;
    }

    public function total(): float
    {
        return round($this->quantity * $this->unitPrice, 2);
    }

    public function costTotal(): float
    {
        return round($this->quantity * $this->unitCost, 2);
    }
}
