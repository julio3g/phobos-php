<?php

declare(strict_types=1);

namespace Phobos\Pricing;

use Phobos\Contracts\PricingStrategyInterface;

/** Markup sobre o custo: preço = custo × (1 + p/100). */
final class MarkupStrategy implements PricingStrategyInterface
{
    public function label(): string
    {
        return 'Markup sobre o custo (%)';
    }

    public function validate(float $marginValue): ?string
    {
        return $marginValue < 0 ? 'O markup não pode ser negativo.' : null;
    }

    public function salePrice(float $totalCost, float $marginValue): float
    {
        return round($totalCost * (1 + $marginValue / 100), 2);
    }
}
