<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Donated = 'donated';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Por entregar',
            self::Delivered => 'Entregado',
            self::Donated => 'Donado (no recogido)',
        };
    }
}
