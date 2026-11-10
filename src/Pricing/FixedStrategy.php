<?php

declare(strict_types=1);

namespace Phobos\Pricing;

use Phobos\Contracts\PricingStrategyInterface;

/** Valor fixo em R$ somado ao custo. */
final class FixedStrategy implements PricingStrategyInterface
{
    public function label(): string
    {
        return 'Valor fixo sobre o custo (R$)';
    }

    public function validate(float $marginValue): ?string
    {
        return $marginValue < 0 ? 'O valor fixo não pode ser negativo.' : null;
    }

    public function salePrice(float $totalCost, float $marginValue): float
    {
        return round($totalCost + $marginValue, 2);
    }
}
