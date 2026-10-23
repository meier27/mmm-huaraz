<?php

namespace App\Enums;

/** Estado del canje de un premio. */
enum ClaimStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Por entregar',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Anulado',
        };
    }
}
