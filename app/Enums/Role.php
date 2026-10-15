<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Cashier = 'cashier';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Editor => 'Editor de contenido',
            self::Cashier => 'Caja',
            self::Delivery => 'Entrega',
        };
    }
}
