<?php

/*
| Reglas de reservas. Valores por defecto prudentes, pendientes de que la
| organización confirme su política (retención, máximos y cancelación).
*/

return [
    // Horas que se retiene una reserva sin pago verificado antes de liberar el stock.
    'hold_hours' => (int) env('RESERVATION_HOLD_HOURS', 24),

    // Máximo de porciones de una misma oferta por reserva.
    'max_quantity_per_item' => (int) env('RESERVATION_MAX_PER_ITEM', 10),

    // Intentos de creación de reservas por IP y hora.
    'attempts_per_hour' => (int) env('RESERVATION_ATTEMPTS_PER_HOUR', 10),
];
