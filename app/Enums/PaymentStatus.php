<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Sin pago',
            self::Partial => 'Adelanto pagado',
            self::Paid => 'Pagado',
        };
    }
}
