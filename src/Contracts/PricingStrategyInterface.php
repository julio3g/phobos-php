<?php

declare(strict_types=1);

namespace Phobos\Contracts;

/** Estratégia de formação do preço de venda a partir do custo total. */
interface PricingStrategyInterface
{
    public function label(): string;

    /** Valida o valor de margem informado; devolve mensagem de erro ou null. */
    public function validate(float $marginValue): ?string;

    public function salePrice(float $totalCost, float $marginValue): float;
}
