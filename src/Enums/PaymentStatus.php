<?php

declare(strict_types=1);

namespace Phobos\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Partial => 'Parcial',
            self::Paid => 'Pago',
            self::Refunded => 'Estornado',
        };
    }
}
