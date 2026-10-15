<?php

namespace App\Exceptions;

use DomainException;

/**
 * Operación de reserva no permitida por su estado actual. El mensaje se
 * muestra al usuario, así que debe ser claro y no exponer detalles internos.
 */
class ReservationException extends DomainException
{
    //
}
