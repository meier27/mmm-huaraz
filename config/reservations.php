<?php

/*
| Reglas de reservas.
| - Cambios y cancelaciones del cliente: hasta "change_deadline_hours" antes
|   del horario reservado (regla confirmada por la organización: 5 horas).
| - Pasado el horario sin recoger, el pedido pagado se registra como donación.
*/

return [
    // Horas antes del horario reservado en que se cierran cambios y cancelaciones.
    'change_deadline_hours' => (int) env('RESERVATION_CHANGE_DEADLINE_HOURS', 5),

    // Horas que se retiene una reserva sin ningún pago antes de liberar el stock
    // (como tarde, se libera al empezar su horario).
    'hold_hours' => (int) env('RESERVATION_HOLD_HOURS', 24),

    // Duración supuesta del horario de recojo cuando la oferta no indica hora de fin.
    'pickup_window_hours' => (int) env('RESERVATION_PICKUP_WINDOW_HOURS', 2),

    // Máximo de porciones de una misma oferta por reserva.
    'max_quantity_per_item' => (int) env('RESERVATION_MAX_PER_ITEM', 10),

    // Intentos de creación de reservas por IP y hora.
    'attempts_per_hour' => (int) env('RESERVATION_ATTEMPTS_PER_HOUR', 10),
];
