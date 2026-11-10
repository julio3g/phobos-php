<?php

declare(strict_types=1);

namespace Phobos\Models;

use DomainException;

/**
 * Linha da composição de um produto (COMPOSIÇÃO: só existe dentro do Product).
 * Referencia o Material (AGREGAÇÃO: o material vive independente do produto)
 * e guarda um snapshot do custo unitário do momento em que foi salva.
 */
final class ProductMaterial
{
    private float $unitCostSnapshot;

    public function __construct(
        private readonly Material $material,
        private float $quantity,
        ?float $unitCostSnapshot = null,
    ) {
        if ($quantity <= 0) {
            throw new DomainException('A quantidade de insumo deve ser maior que zero.');
        }
        $this->unitCostSnapshot = $unitCostSnapshot ?? $material->unitCost();
    }

    public function material(): Material
    {
        return $this->material;
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function unitCostSnapshot(): float
    {
        return $this->unitCostSnapshot;
    }

    public function totalCost(): float
    {
        return round($this->unitCostSnapshot * $this->quantity, 2);
    }

    /** O custo atual do material difere do snapshot salvo? */
    public function isOutdated(): bool
    {
        return abs($this->material->unitCost() - $this->unitCostSnapshot) > 0.000001;
    }

    public function refreshCost(): void
    {
        $this->unitCostSnapshot = $this->material->unitCost();
    }
}
