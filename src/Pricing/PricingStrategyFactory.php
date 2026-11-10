<?php

declare(strict_types=1);

namespace Phobos\Pricing;

use Phobos\Contracts\PricingStrategyInterface;
use Phobos\Enums\MarginType;

final class PricingStrategyFactory
{
    public static function for(MarginType $type): PricingStrategyInterface
    {
        return match ($type) {
            MarginType::Percent => new MarkupStrategy(),
            MarginType::Margin => new MarginStrategy(),
            MarginType::Fixed => new FixedStrategy(),
        };
    }
}
