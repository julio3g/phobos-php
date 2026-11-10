<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;
use Phobos\Enums\MarginType;
use Phobos\Pricing\PricingStrategyFactory;
use Phobos\Traits\Archivable;
use Phobos\Traits\HasTimestamps;

/**
 * Produto: agrega insumos (ProductMaterial) + mão de obra e forma o preço de
 * venda delegando a uma PricingStrategy escolhida pelo MarginType.
 */
final class Product extends Entity
{
    use HasTimestamps;
    use Archivable;

    /** @var list<ProductMaterial> */
    private array $lines = [];
    private ?string $variant = null;
    private ?string $description = null;
    private ?string $imageUrl = null;
    private float $materialCost = 0.0;
    private float $laborCost = 0.0;
    private float $salePrice = 0.0;

    public function __construct(
        private string $name,
        private float $hourlyRate,
        private int $laborMinutes,
        private MarginType $marginType,
        private float $marginValue,
    ) {
        $this->name = self::requireText($name, 'Nome', 150);
        $this->setLabor($hourlyRate, $laborMinutes);
        $this->setMargin($marginType, $marginValue);
    }

    /**
     * @param array<string,mixed>  $row
     * @param list<ProductMaterial> $lines
     */
    public static function fromRow(array $row, array $lines = []): self
    {
        $p = new self(
            (string) $row['name'],
            (float) $row['hourly_rate'],
            (int) $row['labor_minutes'],
            MarginType::from((string) $row['margin_type']),
            (float) $row['margin_value'],
        );
        $p->id = (int) $row['id'];
        $p->variant = $row['variant'] ?? null;
        $p->description = $row['description'] ?? null;
        $p->imageUrl = $row['image_url'] ?? null;
        $p->lines = $lines;
        $p->materialCost = (float) $row['material_cost'];
        $p->laborCost = (float) $row['labor_cost'];
        $p->salePrice = (float) $row['sale_price'];
        $p->hydrateTimestamps($row);
        $p->hydrateArchived($row);

        return $p;
    }

    public function label(): string
    {
        return implode(' · ', array_filter([$this->name, $this->variant]));
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = self::requireText($name, 'Nome', 150);
    }

    public function variant(): ?string
    {
        return $this->variant;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function setDetails(?string $variant, ?string $description): void
    {
        $this->variant = self::nullableText($variant, 'Variação', 100);
        $this->description = self::nullableText($description, 'Descrição', 2000);
    }

    public function imageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): void
    {
        $this->imageUrl = $imageUrl;
    }

    public function hourlyRate(): float
    {
        return $this->hourlyRate;
    }

    public function laborMinutes(): int
    {
        return $this->laborMinutes;
    }

    public function setLabor(float $hourlyRate, int $minutes): void
    {
        if ($hourlyRate < 0 || $minutes < 0) {
            throw new DomainException('Mão de obra não pode ser negativa.');
        }
        $this->hourlyRate = $hourlyRate;
        $this->laborMinutes = $minutes;
    }

    public function marginType(): MarginType
    {
        return $this->marginType;
    }

    public function marginValue(): float
    {
        return $this->marginValue;
    }

    public function setMargin(MarginType $type, float $value): void
    {
        $error = PricingStrategyFactory::for($type)->validate($value);
        if ($error !== null) {
            throw new DomainException($error);
        }
        $this->marginType = $type;
        $this->marginValue = $value;
    }

    /** @return list<ProductMaterial> */
    public function lines(): array
    {
        return $this->lines;
    }

    public function addMaterial(Material $material, float $quantity): void
    {
        foreach ($this->lines as $line) {
            if ($line->material()->id() === $material->id()) {
                throw new DomainException("O insumo \"{$material->label()}\" foi informado mais de uma vez.");
            }
        }
        $this->lines[] = new ProductMaterial($material, $quantity);
    }

    public function clearMaterials(): void
    {
        $this->lines = [];
    }

    public function hasOutdatedLines(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->isOutdated()) {
                return true;
            }
        }

        return false;
    }

    /** Atualiza o snapshot de custo de todas as linhas e recalcula o preço. */
    public function refreshCosts(): void
    {
        foreach ($this->lines as $line) {
            $line->refreshCost();
        }
        $this->recalculate();
    }

    /** Recalcula custos e preço de venda com os dados atuais. */
    public function recalculate(): void
    {
        $this->materialCost = round(array_sum(array_map(static fn (ProductMaterial $l) => $l->totalCost(), $this->lines)), 2);
        $this->laborCost = round(($this->laborMinutes / 60) * $this->hourlyRate, 2);
        $this->salePrice = PricingStrategyFactory::for($this->marginType)
            ->salePrice($this->totalCost(), $this->marginValue);
    }

    public function materialCost(): float
    {
        return $this->materialCost;
    }

    public function laborCost(): float
    {
        return $this->laborCost;
    }

    public function totalCost(): float
    {
        return round($this->materialCost + $this->laborCost, 2);
    }

    public function salePrice(): float
    {
        return $this->salePrice;
    }
}
