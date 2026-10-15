<?php

namespace App\Enums;

enum DonationMethodType: string
{
    case BankTransfer = 'bank_transfer';
    case Yape = 'yape';
    case Plin = 'plin';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Transferencia bancaria',
            self::Yape => 'Yape',
            self::Plin => 'Plin',
            self::Other => 'Otro',
        };
    }
}
