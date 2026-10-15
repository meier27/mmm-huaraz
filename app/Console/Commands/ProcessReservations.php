<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

class ProcessReservations extends Command
{
    protected $signature = 'reservations:process';

    protected $description = 'Libera reservas sin adelanto vencidas y registra como donación los pedidos no recogidos';

    public function handle(ReservationService $reservations): int
    {
        ['expired' => $expired, 'donated' => $donated] = $reservations->processOverdue();

        $this->info("Reservas liberadas: {$expired} · Pedidos registrados como donación: {$donated}");

        return self::SUCCESS;
    }
}
