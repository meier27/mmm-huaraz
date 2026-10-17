<?php

return [
    // Registros de aportes aceptados por IP y hora (freno al spam).
    'reports_per_hour' => (int) env('DONATION_REPORTS_PER_HOUR', 5),
];
