<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';

    protected $description = 'Cancela las reservas sin pago vencidas y libera su stock';

    public function handle(ReservationService $reservations): int
    {
        $count = $reservations->expireOverdue();

        $this->info("Reservas vencidas canceladas: {$count}");

        return self::SUCCESS;
    }
}
