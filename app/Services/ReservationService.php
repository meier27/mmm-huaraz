<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationException;
use App\Models\DishOffering;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Única puerta de entrada para crear reservas y cambiar su estado.
 *
 * Disponibilidad: las ofertas se bloquean (SELECT … FOR UPDATE) dentro de la
 * transacción y el stock se comprueba y descuenta en esa misma operación, de
 * modo que dos solicitudes simultáneas no pueden vender la misma porción.
 * Importes: se calculan aquí con aritmética decimal (bcmath), nunca se
 * reciben del cliente.
 */
class ReservationService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @param  array<int|string, int|string>  $quantities  id de oferta => porciones
     */
    public function create(array $quantities, string $name, string $phone, ?string $notes, string $idempotencyKey): Reservation
    {
        $quantities = array_filter(array_map('intval', $quantities), fn (int $qty) => $qty > 0);

        if ($quantities === []) {
            throw ValidationException::withMessages(['quantities' => 'Selecciona al menos un plato.']);
        }

        if ($existing = Reservation::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(fn () => $this->createLocked($quantities, $name, $phone, $notes, $idempotencyKey));
        } catch (UniqueConstraintViolationException $e) {
            // Solicitud duplicada simultánea: devolver la que ganó.
            return Reservation::where('idempotency_key', $idempotencyKey)->first() ?? throw $e;
        }
    }

    public function verifyPayment(Reservation $reservation, User $by, PaymentMethod $method, ?string $reference): Reservation
    {
        return DB::transaction(function () use ($reservation, $by, $method, $reference) {
            $reservation = $this->lock($reservation);

            if ($reservation->isCancelled()) {
                throw new ReservationException('La reserva está cancelada; no se puede registrar el pago.');
            }

            if ($reservation->isPaid()) {
                throw new ReservationException('El pago de esta reserva ya fue verificado.');
            }

            $reservation->forceFill([
                'payment_status' => PaymentStatus::Verified,
                'status' => ReservationStatus::Confirmed,
                'payment_method' => $method,
                'payment_reference' => $reference ?: null,
                'payment_verified_by' => $by->id,
                'payment_verified_at' => now(),
                'expires_at' => null,
            ])->save();

            return $reservation;
        });
    }

    public function deliver(Reservation $reservation, User $by): Reservation
    {
        return DB::transaction(function () use ($reservation, $by) {
            $reservation = $this->lock($reservation);

            if ($reservation->isCancelled()) {
                throw new ReservationException('La reserva está cancelada; no se puede entregar.');
            }

            if ($reservation->isDelivered()) {
                throw new ReservationException('Esta reserva ya fue entregada el '.$reservation->delivered_at->translatedFormat('d/m H:i').'.');
            }

            if (! $reservation->isPaid()) {
                throw new ReservationException('Primero debe verificarse el pago.');
            }

            $reservation->forceFill([
                'delivery_status' => DeliveryStatus::Delivered,
                'delivered_by' => $by->id,
                'delivered_at' => now(),
            ])->save();

            return $reservation;
        });
    }

    public function cancel(Reservation $reservation, ?User $by, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $by, $reason) {
            $reservation = $this->lock($reservation);

            if ($reservation->isCancelled()) {
                throw new ReservationException('La reserva ya estaba cancelada.');
            }

            if ($reservation->isDelivered()) {
                throw new ReservationException('No se puede cancelar una reserva ya entregada.');
            }

            $items = $reservation->items()->orderBy('dish_offering_id')->get();
            DishOffering::whereIn('id', $items->pluck('dish_offering_id'))->orderBy('id')->lockForUpdate()->get();

            foreach ($items as $item) {
                DishOffering::whereKey($item->dish_offering_id)
                    ->where('reserved', '>=', $item->quantity)
                    ->decrement('reserved', $item->quantity);
            }

            $reservation->forceFill([
                'status' => ReservationStatus::Cancelled,
                'cancelled_by' => $by?->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'expires_at' => null,
            ])->save();

            return $reservation;
        });
    }

    /**
     * Cancela las reservas sin pago cuyo plazo de retención venció y libera su stock.
     */
    public function expireOverdue(): int
    {
        $ids = Reservation::query()
            ->where('status', ReservationStatus::Pending)
            ->where('payment_status', PaymentStatus::Pending)
            ->where('expires_at', '<', now())
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            try {
                $this->cancel(Reservation::findOrFail($id), null, 'Vencida sin pago verificado');
                $expired++;
            } catch (ReservationException) {
                // Cambió de estado mientras tanto (p. ej. se pagó): se respeta.
            }
        }

        return $expired;
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function createLocked(array $quantities, string $name, string $phone, ?string $notes, string $idempotencyKey): Reservation
    {
        $offerings = DishOffering::query()
            ->bookable()
            ->with('dish:id,name')
            ->whereIn('id', array_keys($quantities))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $max = config('reservations.max_quantity_per_item');
        $total = '0.00';
        $lines = [];

        foreach ($quantities as $offeringId => $quantity) {
            $offering = $offerings->get($offeringId);

            if (! $offering) {
                throw ValidationException::withMessages(['quantities' => 'Una de las opciones seleccionadas ya no está disponible. Actualiza la página.']);
            }

            if ($quantity > $max) {
                throw ValidationException::withMessages(["quantities.$offeringId" => "Puedes reservar como máximo {$max} porciones de cada opción."]);
            }

            if ($offering->available() < $quantity) {
                $available = $offering->available();

                throw ValidationException::withMessages(["quantities.$offeringId" => $available === 0
                    ? "«{$offering->dish->name}» se ha agotado para ese horario."
                    : "Solo quedan {$available} porciones de «{$offering->dish->name}» para ese horario."]);
            }

            $lineTotal = bcmul((string) $offering->price, (string) $quantity, 2);
            $total = bcadd($total, $lineTotal, 2);
            $lines[] = [$offering, $quantity, $lineTotal];
        }

        $token = bin2hex(random_bytes(24));

        $reservation = new Reservation;
        $reservation->forceFill([
            'code' => $this->uniqueCode(),
            'token_hash' => Reservation::hashToken($token),
            'token_encrypted' => $token,
            'idempotency_key' => $idempotencyKey,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'notes' => $notes ?: null,
            'total' => $total,
            'status' => ReservationStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'delivery_status' => DeliveryStatus::Pending,
            'expires_at' => now()->addHours(config('reservations.hold_hours')),
        ])->save();

        foreach ($lines as [$offering, $quantity, $lineTotal]) {
            (new ReservationItem)->forceFill([
                'reservation_id' => $reservation->id,
                'dish_offering_id' => $offering->id,
                'quantity' => $quantity,
                'unit_price' => $offering->price,
                'line_total' => $lineTotal,
            ])->save();

            // Sin eventos de modelo: el cambio queda auditado en la propia reserva.
            DishOffering::whereKey($offering->id)->increment('reserved', $quantity);
        }

        return $reservation;
    }

    private function lock(Reservation $reservation): Reservation
    {
        return Reservation::query()->lockForUpdate()->findOrFail($reservation->id);
    }

    private function uniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (Reservation::where('code', $code)->exists());

        return $code;
    }
}
