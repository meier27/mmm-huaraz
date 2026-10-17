<?php

namespace App\Exceptions;

use DomainException;

/**
 * Regla de negocio de aportes incumplida. El mensaje se muestra al usuario.
 */
class DonationException extends DomainException {}
