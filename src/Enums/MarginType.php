<?php

declare(strict_types=1);

namespace Phobos\Enums;

enum MarginType: string
{
    case Percent = 'percent';
    case Margin = 'margin';
    case Fixed = 'fixed';
}
