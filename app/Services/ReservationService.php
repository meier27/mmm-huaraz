<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationException;
use App\Models\AuditLog;
use App\Models\ChurchEvent;
use App\Models\DishOffering;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\ReservationPayment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Única puerta de entrada para crear reservas y cambiar su estado.
 *
 * Disponibilidad: las ofertas se bloquean (SELECT … FOR UPDATE) dentro de la
 * transacción y el stock se comprueba y descuenta en esa misma operación, de
 * modo que dos solicitudes simultáneas no pueden vender la misma porción.
 * Importes: se calculan aquí con aritmética decimal (bcmath), nunca se
 * reciben del cliente.
 *
 * Reglas de la organización:
 * - Se paga por adelantado al menos una parte; el saldo se cobra al recoger.
 * - El cliente puede cambiar su pedido (o cancelarlo si no pagó nada) hasta
 *   N horas antes del horario reservado (config reservations.change_deadline_hours).
 * - Si no se recoge, al terminar el horario el pedido pagado pasa a donación.
 */
class ReservationService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @param  array<int|string, int|string>  $quantities  id de oferta => porciones
     * @param  ChurchEvent|null  $event  Evento de la página desde la que se reserva: si se indica,
     *                                   solo se aceptan platos de ese evento.
     * @param  UploadedFile|null  $depositProof  Captura del adelanto: con ella la reserva no
     *                                           vence sola; espera la revisión del personal.
     */
    public function create(array $quantities, string $name, string $phone, ?string $notes, string $idempotencyKey, ?ChurchEvent $event = null, ?UploadedFile $depositProof = null): Reservation
    {
        $quantities = $this->cleanQuantities($quantities);

        if ($existing = Reservation::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        $proofPath = $depositProof?->store('reservation-proofs', Reservation::PROOF_DISK);

        try {
            return DB::transaction(function () use ($quantities, $name, $phone, $notes, $idempotencyKey, $event, $proofPath) {
                $lines = $this->reserveStock($quantities, $event?->id);
                $token = bin2hex(random_bytes(24));

                $reservation = new Reservation;
                $reservation->forceFill([
                    'church_event_id' => $lines[0][0]->dish->church_event_id,
                    'code' => $this->uniqueCode(),
                    'token_hash' => Reservation::hashToken($token),
                    'token_encrypted' => $token,
                    'idempotency_key' => $idempotencyKey,
                    'customer_name' => $name,
                    'customer_phone' => $phone,
                    'notes' => $notes ?: null,
                    'deposit_proof_path' => $proofPath,
                    'total' => $this->sum($lines),
                    'amount_paid' => '0.00',
                    'status' => ReservationStatus::Pending,
                    'payment_status' => PaymentStatus::Pending,
                    'delivery_status' => DeliveryStatus::Pending,
                    'expires_at' => $proofPath ? null : $this->holdUntil($lines),
                ])->save();

                $this->storeItems($reservation, $lines);

                return $reservation;
            });
        } catch (\Throwable $e) {
            if ($proofPath) {
                Storage::disk(Reservation::PROOF_DISK)->delete($proofPath);
            }

            // Solicitud duplicada simultánea: devolver la que ganó.
            if ($e instanceof UniqueConstraintViolationException) {
                return Reservation::where('idempotency_key', $idempotencyKey)->first() ?? throw $e;
            }

            throw $e;
        }
    }

    /**
     * Acepta la captura del adelanto: registra como pago el adelanto exigido
     * por el evento y confirma la reserva.
     */
    public function approveDeposit(Reservation $reservation, User $by, PaymentMethod $method): Reservation
    {
        return DB::transaction(function () use ($reservation, $by, $method) {
            $reservation = $this->lock($reservation);

            if ($reservation->deposit_proof_path === null) {
                throw new ReservationException('Esta reserva no tiene captura del adelanto.');
            }

            if ($reservation->hasPayments()) {
                throw new ReservationException('El adelanto de esta reserva ya fue aceptado.');
            }

            $amount = $reservation->event->minimumDeposit((string) $reservation->total);

            if (bccomp($amount, '0', 2) <= 0) {
                throw new ReservationException('El evento no tiene un adelanto configurado: indícalo en la ficha del evento o registra el pago a mano.');
            }

            return $this->registerPayment($reservation, $by, $amount, $method, 'Captura del adelanto');
        });
    }

    /**
     * Registra un pago verificado por el personal (adelanto o saldo).
     */
    public function registerPayment(Reservation $reservation, User $by, string $amount, PaymentMethod $method, ?string $reference): Reservation
    {
        return DB::transaction(function () use ($reservation, $by, $amount, $method, $reference) {
            $reservation = $this->lock($reservation);
            $this->assertOpen($reservation, 'registrar pagos');

            $amount = bcadd($amount, '0', 2);
            $balance = $reservation->balance();

            if (bccomp($amount, '0', 2) <= 0) {
                throw new ReservationException('El monto debe ser mayor que cero.');
            }

            if (bccomp($balance, '0', 2) <= 0) {
                throw new ReservationException('Esta reserva ya está pagada por completo.');
            }

            if (bccomp($amount, $balance, 2) > 0) {
                throw new ReservationException("El monto supera el saldo pendiente (S/ {$balance}).");
            }

            $minimum = $reservation->event->minimumDeposit((string) $reservation->total);
            if (! $reservation->hasPayments() && bccomp($amount, $minimum, 2) < 0) {
                throw new ReservationException("El adelanto mínimo para esta reserva es S/ {$minimum}.");
            }

            (new ReservationPayment)->forceFill([
                'reservation_id' => $reservation->id,
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference ?: null,
                'received_by' => $by->id,
            ])->save();

            $paid = bcadd((string) $reservation->amount_paid, $amount, 2);

            $reservation->forceFill([
                'amount_paid' => $paid,
                'payment_status' => bccomp($paid, (string) $reservation->total, 2) >= 0 ? PaymentStatus::Paid : PaymentStatus::Partial,
                'status' => ReservationStatus::Confirmed,
                'expires_at' => null,
            ])->save();

            return $reservation;
        });
    }

    public function deliver(Reservation $reservation, User $by): Reservation
    {
        return DB::transaction(function () use ($reservation, $by) {
            $reservation = $this->lock($reservation);

            if ($reservation->isDelivered()) {
                throw new ReservationException('Esta reserva ya fue entregada el '.$reservation->delivered_at->translatedFormat('d/m H:i').'.');
            }

            $this->assertOpen($reservation, 'entregar');

            if (! $reservation->isFullyPaid()) {
                throw new ReservationException("Falta cobrar el saldo de S/ {$reservation->balance()} antes de entregar.");
            }

            $reservation->forceFill([
                'delivery_status' => DeliveryStatus::Delivered,
                'delivered_by' => $by->id,
                'delivered_at' => now(),
            ])->save();

            return $reservation;
        });
    }

    /**
     * Cancelación por el personal (o automática, $by = null).
     */
    public function cancel(Reservation $reservation, ?User $by, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $by, $reason) {
            $reservation = $this->lock($reservation);

            if ($reservation->isCancelled()) {
                throw new ReservationException('La reserva ya estaba cancelada.');
            }

            $this->assertOpen($reservation, 'cancelar');
            $this->releaseStock($reservation);

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
     * Cancelación por el propio cliente: solo sin pagos y dentro del plazo.
     * Si ya pagó, puede cambiar su pedido, pero no cancelarlo.
     */
    public function cancelByCustomer(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation = $this->lock($reservation);
            $this->assertCustomerCanChange($reservation);

            if ($reservation->hasPayments()) {
                throw new ReservationException('Ya registraste un pago: puedes cambiar tu pedido por otra comida, pero no cancelarlo.');
            }

            return $this->cancel($reservation, null, 'Cancelada por el cliente');
        });
    }

    /**
     * Cambia el pedido por otras ofertas conservando el código, el enlace y lo pagado.
     *
     * @param  array<int|string, int|string>  $quantities
     */
    public function change(Reservation $reservation, array $quantities): Reservation
    {
        $quantities = $this->cleanQuantities($quantities);

        return DB::transaction(function () use ($reservation, $quantities) {
            $reservation = $this->lock($reservation);
            $this->assertCustomerCanChange($reservation);

            $before = $this->itemsSummary($reservation);
            $this->releaseStock($reservation);
            $reservation->items()->delete();

            // El nuevo pedido debe ser del mismo evento que la reserva.
            $lines = $this->reserveStock($quantities, $reservation->church_event_id);
            $total = $this->sum($lines);

            if (bccomp($total, (string) $reservation->amount_paid, 2) < 0) {
                throw ValidationException::withMessages([
                    'quantities' => "El nuevo pedido debe costar al menos lo que ya pagaste (S/ {$reservation->amount_paid}).",
                ]);
            }

            $this->storeItems($reservation, $lines);
            $paid = (string) $reservation->amount_paid;

            $reservation->forceFill([
                'total' => $total,
                'payment_status' => match (true) {
                    bccomp($paid, '0', 2) === 0 => PaymentStatus::Pending,
                    bccomp($paid, $total, 2) >= 0 => PaymentStatus::Paid,
                    default => PaymentStatus::Partial,
                },
                'expires_at' => $reservation->hasPayments() ? null : $this->holdUntil($lines),
                'changed_at' => now(),
            ])->save();

            AuditLog::record('changed', $reservation, ['items' => $before], ['items' => $this->itemsSummary($reservation)]);

            return $reservation;
        });
    }

    /**
     * Registra como donación un pedido pagado (total o parcialmente) que no se recogió.
     * Solo es posible cuando ya pasó el plazo de cambios.
     */
    public function markDonated(Reservation $reservation, ?User $by): Reservation
    {
        return DB::transaction(function () use ($reservation, $by) {
            $reservation = $this->lock($reservation);
            $this->assertOpen($reservation, 'donar');

            if (! $reservation->hasPayments()) {
                throw new ReservationException('Una reserva sin pagos no se dona: se cancela.');
            }

            if ($reservation->customerCanChange()) {
                throw new ReservationException('El cliente todavía está a tiempo de cambiar su pedido.');
            }

            $reservation->forceFill([
                'delivery_status' => DeliveryStatus::Donated,
                'donated_by' => $by?->id,
                'donated_at' => now(),
            ])->save();

            return $reservation;
        });
    }

    /**
     * Tarea programada: libera reservas sin pago vencidas y registra como
     * donación los pedidos pagados no recogidos cuyo horario ya terminó.
     *
     * @return array{expired: int, donated: int}
     */
    public function processOverdue(): array
    {
        $expired = 0;
        $donated = 0;

        $unpaid = Reservation::query()
            ->where('status', ReservationStatus::Pending)
            ->where('amount_paid', 0)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        foreach ($unpaid as $id) {
            try {
                $this->cancel(Reservation::findOrFail($id), null, 'Vencida sin adelanto');
                $expired++;
            } catch (ReservationException) {
                // Cambió de estado mientras tanto (p. ej. se pagó): se respeta.
            }
        }

        $undelivered = Reservation::query()
            ->with('items.offering')
            ->where('status', ReservationStatus::Confirmed)
            ->where('delivery_status', DeliveryStatus::Pending)
            ->get()
            ->filter(fn (Reservation $r) => $r->lastSlotEnd()?->isPast());

        foreach ($undelivered as $reservation) {
            try {
                $this->markDonated($reservation, null);
                $donated++;
            } catch (ReservationException) {
                //
            }
        }

        return ['expired' => $expired, 'donated' => $donated];
    }

    /**
     * @param  array<int|string, int|string>  $quantities
     * @return array<int, int>
     */
    private function cleanQuantities(array $quantities): array
    {
        $quantities = array_filter(array_map('intval', $quantities), fn (int $qty) => $qty > 0);

        if ($quantities === []) {
            throw ValidationException::withMessages(['quantities' => 'Selecciona al menos un plato.']);
        }

        return $quantities;
    }

    /**
     * Bloquea las ofertas, valida disponibilidad y descuenta el stock.
     * Todas deben ser del mismo evento (y de $eventId, si se indica): una
     * reserva pertenece a un único evento.
     *
     * @param  array<int, int>  $quantities
     * @return list<array{0: DishOffering, 1: int, 2: string}>
     */
    private function reserveStock(array $quantities, ?int $eventId = null): array
    {
        $offerings = DishOffering::query()
            ->bookable()
            ->when($eventId, fn ($q) => $q->forEvent($eventId))
            ->with('dish:id,name,church_event_id')
            ->whereIn('id', array_keys($quantities))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $max = config('reservations.max_quantity_per_item');
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

            $lines[] = [$offering, $quantity, bcmul((string) $offering->price, (string) $quantity, 2)];
        }

        if (collect($lines)->map(fn ($line) => $line[0]->dish->church_event_id)->unique()->count() > 1) {
            throw ValidationException::withMessages(['quantities' => 'Una reserva solo puede incluir platos de un mismo evento.']);
        }

        foreach ($lines as [$offering, $quantity]) {
            // Sin eventos de modelo: el cambio queda auditado en la propia reserva.
            DishOffering::whereKey($offering->id)->increment('reserved', $quantity);
        }

        return $lines;
    }

    private function releaseStock(Reservation $reservation): void
    {
        $items = $reservation->items()->orderBy('dish_offering_id')->get();
        DishOffering::whereIn('id', $items->pluck('dish_offering_id'))->orderBy('id')->lockForUpdate()->get();

        foreach ($items as $item) {
            DishOffering::whereKey($item->dish_offering_id)
                ->where('reserved', '>=', $item->quantity)
                ->decrement('reserved', $item->quantity);
        }
    }

    /**
     * @param  list<array{0: DishOffering, 1: int, 2: string}>  $lines
     */
    private function storeItems(Reservation $reservation, array $lines): void
    {
        foreach ($lines as [$offering, $quantity, $lineTotal]) {
            (new ReservationItem)->forceFill([
                'reservation_id' => $reservation->id,
                'dish_offering_id' => $offering->id,
                'quantity' => $quantity,
                'unit_price' => $offering->price,
                'line_total' => $lineTotal,
            ])->save();
        }
    }

    /**
     * @param  list<array{0: DishOffering, 1: int, 2: string}>  $lines
     */
    private function sum(array $lines): string
    {
        return array_reduce($lines, fn (string $total, array $line) => bcadd($total, $line[2], 2), '0.00');
    }

    /**
     * Una reserva sin pago se retiene como máximo hasta el inicio de su primer horario.
     *
     * @param  list<array{0: DishOffering, 1: int, 2: string}>  $lines
     */
    private function holdUntil(array $lines): Carbon
    {
        $firstStart = (new Collection($lines))->map(fn (array $line) => $line[0]->startsAt())->min();

        return now()->addHours(config('reservations.hold_hours'))->min($firstStart);
    }

    /**
     * @return list<string>
     */
    private function itemsSummary(Reservation $reservation): array
    {
        return $reservation->items()->with('offering.dish:id,name')->get()
            ->map(fn (ReservationItem $i) => "{$i->quantity} × {$i->offering->dish->name} ({$i->offering->serve_date->toDateString()} {$i->offering->slotLabel()}) S/ {$i->line_total}")
            ->all();
    }

    private function assertOpen(Reservation $reservation, string $action): void
    {
        if ($reservation->isCancelled()) {
            throw new ReservationException("La reserva está cancelada; no se puede {$action}.");
        }

        if ($reservation->isDelivered()) {
            throw new ReservationException("La reserva ya fue entregada; no se puede {$action}.");
        }

        if ($reservation->isDonated()) {
            throw new ReservationException("El pedido ya se registró como donación; no se puede {$action}.");
        }
    }

    private function assertCustomerCanChange(Reservation $reservation): void
    {
        $this->assertOpen($reservation, 'modificar');
        $reservation->load('items.offering');

        if (! $reservation->customerCanChange()) {
            $hours = config('reservations.change_deadline_hours');

            throw new ReservationException("Los cambios y cancelaciones solo se aceptan hasta {$hours} horas antes del horario reservado.");
        }
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
