<?php

namespace App\Enums;

enum AccommodationType: string
{
    case Hotel = 'hotel';
    case Hostel = 'hostel';
    case FamilyHome = 'family_home';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hotel => 'Hotel',
            self::Hostel => 'Hostal',
            self::FamilyHome => 'Casa de familia',
            self::Other => 'Otro',
        };
    }
}
