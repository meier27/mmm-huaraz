<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Por verificar',
            self::Verified => 'Verificado',
            self::Rejected => 'Rechazado',
        };
    }
}
