<?php

use Illuminate\Support\Facades\Schedule;

// Requiere en el servidor: * * * * * php artisan schedule:run
Schedule::command('reservations:process')->everyFifteenMinutes()->withoutOverlapping();
