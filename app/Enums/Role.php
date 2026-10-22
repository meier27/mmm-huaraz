<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    // Valor interno 'cashier' conservado: es el que guardan los usuarios existentes.
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Editor => 'Editor de contenido',
            self::Cashier => 'Tesorería',
        };
    }
}
