<?php

declare(strict_types=1);

namespace Phobos\Enums;

enum Unit: string
{
    case Un = 'un';
    case M = 'm';
    case G = 'g';
    case Kg = 'kg';
    case Ml = 'ml';
    case Pair = 'pair';

    public function label(): string
    {
        return match ($this) {
            self::Un => 'Unidade',
            self::M => 'Metro',
            self::G => 'Grama',
            self::Kg => 'Quilo',
            self::Ml => 'Mililitro',
            self::Pair => 'Par',
        };
    }
}
