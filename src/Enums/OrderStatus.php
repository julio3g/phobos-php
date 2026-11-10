<?php

declare(strict_types=1);

namespace Phobos\Enums;

/** Ciclo de vida da ordem; as transições permitidas ficam no próprio enum. */
enum OrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case InProduction = 'in_production';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Confirmed => 'Confirmada',
            self::InProduction => 'Em produção',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::Draft => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::InProduction, self::Cancelled],
            self::InProduction => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->next(), true);
    }
}
