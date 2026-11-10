<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;
use Phobos\Enums\Unit;
use Phobos\Traits\Archivable;
use Phobos\Traits\HasTimestamps;

/** Insumo comprado em embalagem; o custo unitário é derivado (preço ÷ quantidade). */
final class Material extends Entity
{
    use HasTimestamps;
    use Archivable;

    private string $name;
    private Unit $unit;
    private float $packagePrice;
    private float $packageQuantity;
    private ?string $brand = null;
    private ?string $variant = null;
    private ?string $imageUrl = null;
    private ?string $purchaseUrl = null;

    public function __construct(string $name, Unit $unit, float $packagePrice, float $packageQuantity)
    {
        $this->setName($name);
        $this->unit = $unit;
        $this->setPackage($packagePrice, $packageQuantity);
    }

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        $m = new self(
            (string) $row['name'],
            Unit::from((string) $row['unit']),
            (float) $row['package_price'],
            (float) $row['package_quantity'],
        );
        $m->id = (int) $row['id'];
        $m->setDetails($row['brand'] ?? null, $row['variant'] ?? null, $row['purchase_url'] ?? null);
        $m->imageUrl = $row['image_url'] ?? null;
        $m->hydrateTimestamps($row);
        $m->hydrateArchived($row);

        return $m;
    }

    public function label(): string
    {
        return implode(' · ', array_filter([$this->name, $this->brand, $this->variant]));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = self::requireText($name, 'Nome', 150);
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function setUnit(Unit $unit): void
    {
        $this->unit = $unit;
    }

    public function packagePrice(): float
    {
        return $this->packagePrice;
    }

    public function packageQuantity(): float
    {
        return $this->packageQuantity;
    }

    public function setPackage(float $price, float $quantity): void
    {
        if ($price < 0) {
            throw new DomainException('O preço da embalagem não pode ser negativo.');
        }
        if ($quantity <= 0) {
            throw new DomainException('A quantidade da embalagem deve ser maior que zero.');
        }
        $this->packagePrice = $price;
        $this->packageQuantity = $quantity;
    }

    /** Custo por unidade-base (preço da embalagem ÷ quantidade). */
    public function unitCost(): float
    {
        return $this->packagePrice / $this->packageQuantity;
    }

    public function brand(): ?string
    {
        return $this->brand;
    }

    public function variant(): ?string
    {
        return $this->variant;
    }

    public function purchaseUrl(): ?string
    {
        return $this->purchaseUrl;
    }

    public function setDetails(?string $brand, ?string $variant, ?string $purchaseUrl): void
    {
        $this->brand = self::nullableText($brand, 'Marca', 100);
        $this->variant = self::nullableText($variant, 'Variação', 100);
        $this->purchaseUrl = self::nullableText($purchaseUrl, 'Link de compra', 500);
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }
}
