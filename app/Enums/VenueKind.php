<?php

namespace App\Enums;

enum VenueKind: string
{
    case Main = 'main';
    case HostChurch = 'host_church';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Sede principal',
            self::HostChurch => 'Iglesia anfitriona',
            self::Other => 'Otro punto',
        };
    }
}
