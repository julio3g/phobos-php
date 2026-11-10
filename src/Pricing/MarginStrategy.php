<?php

declare(strict_types=1);

namespace Phobos\Pricing;

use Phobos\Contracts\PricingStrategyInterface;

/** Margem sobre a venda: preço = custo / (1 − p/100), com p < 100. */
final class MarginStrategy implements PricingStrategyInterface
{
    public function label(): string
    {
        return 'Margem sobre a venda (%)';
    }

    public function validate(float $marginValue): ?string
    {
        if ($marginValue < 0) {
            return 'A margem não pode ser negativa.';
        }

        return $marginValue >= 100 ? 'A margem sobre a venda deve ser menor que 100%.' : null;
    }

    public function salePrice(float $totalCost, float $marginValue): float
    {
        return round($totalCost / (1 - $marginValue / 100), 2);
    }
}
