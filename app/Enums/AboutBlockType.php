<?php

namespace App\Enums;

/** Estilos de los bloques de «Nosotros». */
enum AboutBlockType: string
{
    case Text = 'text';
    case Highlight = 'highlight';
    case Quote = 'quote';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto (tarjeta blanca)',
            self::Highlight => 'Texto destacado (tarjeta naranja)',
            self::Quote => 'Cita con autor (tarjeta oscura)',
        };
    }
}
