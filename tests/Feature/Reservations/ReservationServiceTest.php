<?php

namespace Tests\Feature\Reservations;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationException;
use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesOfferings;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use CreatesOfferings, RefreshDatabase;

    private ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReservationService::class);
    }

    private function reserve(array $quantities, ?string $key = null): Reservation
    {
        return $this->service->create($quantities, 'Ana Prueba', '+51 900 000 000', null, $key ?? (string) Str::uuid());
    }

    private function pay(Reservation $reservation, string $amount, ?User $by = null): Reservation
    {
        return $this->service->registerPayment($reservation, $by ?? User::factory()->create(), $amount, PaymentMethod::Yape, null);
    }

    // ---------- Creación y stock ----------

    public function test_it_creates_a_reservation_computing_amounts_on_the_server(): void
    {
        $a = $this->offering(['price' => '12.50']);
        $b = $this->offering(['price' => '7.30'], ['name' => 'Otro plato']);

        $reservation = $this->reserve([$a->id => 3, $b->id => 2]);

        $this->assertSame('52.10', $reservation->fresh()->total);
        $this->assertSame('0.00', $reservation->fresh()->amount_paid);
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertSame(PaymentStatus::Pending, $reservation->payment_status);
        $this->assertSame(DeliveryStatus::Pending, $reservation->delivery_status);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $reservation->code);
        $this->assertSame(3, $a->fresh()->reserved);
        $this->assertSame(2, $b->fresh()->reserved);
        $this->assertSame(['12.50', '7.30'], $reservation->items()->orderBy('id')->pluck('unit_price')->all());
    }

    public function test_unpaid_hold_never_extends_past_the_slot_start(): void
    {
        config(['reservations.hold_hours' => 72]);
        $offering = $this->offering(['serve_date' => today()->addDay()->toDateString(), 'slot_start' => '12:00']);

        $reservation = $this->reserve([$offering->id => 1]);

        $this->assertTrue($reservation->expires_at->equalTo(Carbon::parse(today()->addDay()->toDateString().' 12:00')));
    }

    public function test_token_is_stored_hashed_and_resolvable(): void
    {
        $reservation = $this->reserve([$this->offering()->id => 1]);
        $token = $reservation->token_encrypted;

        $this->assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $token);
        $this->assertSame(hash('sha256', $token), $reservation->token_hash);
        $this->assertDatabaseMissing('reservations', ['token_encrypted' => $token]);
        $this->assertTrue(Reservation::findByToken($token)->is($reservation));
        $this->assertNull(Reservation::findByToken(str_repeat('a', 48)));
    }

    public function test_price_changes_do_not_alter_existing_reservations(): void
    {
        $offering = $this->offering(['price' => '10.00']);
        $reservation = $this->reserve([$offering->id => 2]);

        $offering->update(['price' => '15.00']);

        $this->assertSame('20.00', $reservation->fresh()->total);
        $this->assertSame('10.00', $reservation->items()->first()->unit_price);
    }

    public function test_insufficient_stock_is_rejected_without_side_effects(): void
    {
        $offering = $this->offering(['stock' => 3]);

        try {
            $this->reserve([$offering->id => 4]);
            $this->fail('Se esperaba ValidationException.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Solo quedan 3', $e->errors()["quantities.{$offering->id}"][0]);
        }

        $this->assertSame(0, $offering->fresh()->reserved);
        $this->assertSame(0, Reservation::count());
    }

    public function test_last_units_cannot_be_sold_twice(): void
    {
        $offering = $this->offering(['stock' => 2]);
        $this->reserve([$offering->id => 2]);

        $this->expectException(ValidationException::class);
        $this->reserve([$offering->id => 1]);
    }

    public function test_quantity_above_the_per_item_maximum_is_rejected(): void
    {
        config(['reservations.max_quantity_per_item' => 5]);
        $offering = $this->offering(['stock' => 50]);

        $this->expectException(ValidationException::class);
        $this->reserve([$offering->id => 6]);
    }

    public function test_empty_or_non_positive_quantities_are_rejected(): void
    {
        $offering = $this->offering();

        $this->expectException(ValidationException::class);
        $this->reserve([$offering->id => 0, 999 => -3]);
    }

    public function test_unbookable_offerings_are_rejected(): void
    {
        $this->travelTo(Carbon::parse(today()->toDateString().' 13:00'));

        $cases = [
            'plato sin publicar' => $this->offering([], ['is_published' => false]),
            'oferta inactiva' => $this->offering(['is_active' => false]),
            'fecha pasada' => $this->offering(['serve_date' => today()->subDay()->toDateString()]),
            'horario de hoy ya iniciado' => $this->offering(['serve_date' => today()->toDateString(), 'slot_start' => '12:00']),
        ];

        foreach ($cases as $case => $offering) {
            try {
                $this->reserve([$offering->id => 1]);
                $this->fail("Debió rechazarse: $case");
            } catch (ValidationException) {
                $this->assertSame(0, $offering->fresh()->reserved, $case);
            }
        }

        // Un horario posterior del mismo día sí se puede reservar.
        $dinner = $this->offering(['serve_date' => today()->toDateString(), 'slot_start' => '19:00']);
        $this->assertSame(1, $this->reserve([$dinner->id => 1])->items()->count());
    }

    public function test_same_idempotency_key_returns_the_same_reservation(): void
    {
        $offering = $this->offering();
        $key = (string) Str::uuid();

        $first = $this->reserve([$offering->id => 2], $key);
        $second = $this->reserve([$offering->id => 2], $key);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, Reservation::count());
        $this->assertSame(2, $offering->fresh()->reserved);
    }

    // ---------- Pagos parciales ----------

    public function test_partial_advance_confirms_the_reservation_and_balance_is_paid_at_pickup(): void
    {
        $cashier = User::factory()->create();
        $reservation = $this->reserve([$this->offering(['price' => '15.00'])->id => 2]); // 30.00

        $this->pay($reservation, '10', $cashier);
        $reservation->refresh();
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(PaymentStatus::Partial, $reservation->payment_status);
        $this->assertSame('10.00', $reservation->amount_paid);
        $this->assertSame('20.00', $reservation->balance());
        $this->assertNull($reservation->expires_at);

        $this->pay($reservation, '20.00', $cashier);
        $reservation->refresh();
        $this->assertSame(PaymentStatus::Paid, $reservation->payment_status);
        $this->assertSame(2, $reservation->payments()->count());
        $this->assertSame($cashier->id, $reservation->payments()->first()->received_by);

        $this->service->deliver($reservation, $cashier);
        $this->assertSame(DeliveryStatus::Delivered, $reservation->fresh()->delivery_status);
    }

    public function test_payment_cannot_exceed_the_balance_or_be_zero(): void
    {
        $reservation = $this->reserve([$this->offering(['price' => '10.00'])->id => 1]);

        foreach (['10.01', '0', '-5'] as $amount) {
            try {
                $this->pay($reservation, $amount);
                $this->fail("Debió rechazarse el monto $amount");
            } catch (ReservationException) {
                $this->assertSame('0.00', $reservation->fresh()->amount_paid);
            }
        }
    }

    public function test_fully_paid_reservation_rejects_more_payments(): void
    {
        $reservation = $this->reserve([$this->offering(['price' => '10.00'])->id => 1]);
        $this->pay($reservation, '10.00');

        $this->expectExceptionMessage('pagada por completo');
        $this->pay($reservation, '1.00');
    }

    public function test_minimum_deposit_percentage_applies_to_the_first_payment_only(): void
    {
        $this->foodEvent()->update(['deposit_min_percent' => 50]);
        $reservation = $this->reserve([$this->offering(['price' => '15.00'])->id => 1]); // mínimo 7.50

        try {
            $this->pay($reservation, '7.49');
            $this->fail('Debió exigirse el adelanto mínimo.');
        } catch (ReservationException $e) {
            $this->assertStringContainsString('S/ 7.50', $e->getMessage());
        }

        $this->pay($reservation, '7.50');
        $this->pay($reservation, '1.00'); // pagos posteriores sin mínimo
        $this->assertSame('8.50', $reservation->fresh()->amount_paid);
    }

    public function test_delivery_requires_the_full_balance(): void
    {
        $reservation = $this->reserve([$this->offering(['price' => '10.00'])->id => 1]);
        $this->pay($reservation, '4.00');

        $this->expectExceptionMessage('Falta cobrar el saldo de S/ 6.00');
        $this->service->deliver($reservation, User::factory()->create());
    }

    public function test_delivery_cannot_be_registered_twice(): void
    {
        $user = User::factory()->create();
        $reservation = $this->reserve([$this->offering()->id => 1]);
        $this->pay($reservation, $reservation->total);
        $this->service->deliver($reservation, $user);

        $this->expectExceptionMessage('ya fue entregada');
        $this->service->deliver($reservation, $user);
    }

    // ---------- Cambios del cliente (plazo de 5 horas) ----------

    public function test_customer_changes_order_keeping_payment_code_and_token(): void
    {
        $lunch = $this->offering(['price' => '10.00', 'stock' => 5]);
        $dinner = $this->offering(['price' => '12.00', 'slot_start' => '19:00', 'slot_end' => '21:00', 'stock' => 5]);
        $reservation = $this->reserve([$lunch->id => 2]); // 20.00
        $this->pay($reservation, '8.00');
        $code = $reservation->code;
        $hash = $reservation->token_hash;

        $this->service->change($reservation, [$dinner->id => 2]); // 24.00

        $reservation->refresh();
        $this->assertSame([$code, $hash], [$reservation->code, $reservation->token_hash]);
        $this->assertSame('24.00', $reservation->total);
        $this->assertSame('8.00', $reservation->amount_paid);
        $this->assertSame(PaymentStatus::Partial, $reservation->payment_status);
        $this->assertSame(0, $lunch->fresh()->reserved);
        $this->assertSame(2, $dinner->fresh()->reserved);
        $this->assertNotNull($reservation->changed_at);
        $this->assertSame('changed', AuditLog::latest('id')->first()->action);
    }

    public function test_change_is_rejected_after_the_deadline(): void
    {
        config(['reservations.change_deadline_hours' => 5]);
        $lunch = $this->offering(['serve_date' => today()->addDay()->toDateString(), 'slot_start' => '12:00']);
        $dinner = $this->offering(['serve_date' => today()->addDay()->toDateString(), 'slot_start' => '19:00']);
        $reservation = $this->reserve([$lunch->id => 1]);

        $this->travelTo(Carbon::parse(today()->addDay()->toDateString().' 07:00')); // exactamente 5 h antes

        $this->expectExceptionMessage('5 horas antes');
        $this->service->change($reservation, [$dinner->id => 1]);
    }

    public function test_change_just_before_the_deadline_is_accepted(): void
    {
        $lunch = $this->offering(['serve_date' => today()->addDay()->toDateString(), 'slot_start' => '12:00']);
        $dinner = $this->offering(['serve_date' => today()->addDay()->toDateString(), 'slot_start' => '19:00']);
        $reservation = $this->reserve([$lunch->id => 1]);

        $this->travelTo(Carbon::parse(today()->addDay()->toDateString().' 06:59'));

        $this->service->change($reservation, [$dinner->id => 1]);
        $this->assertSame(1, $dinner->fresh()->reserved);
    }

    public function test_new_order_must_cover_what_was_already_paid_and_failure_keeps_original(): void
    {
        $expensive = $this->offering(['price' => '20.00']);
        $cheap = $this->offering(['price' => '5.00', 'slot_start' => '19:00']);
        $reservation = $this->reserve([$expensive->id => 1]);
        $this->pay($reservation, '15.00');

        try {
            $this->service->change($reservation, [$cheap->id => 1]);
            $this->fail('El nuevo pedido debía cubrir lo pagado.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('S/ 15.00', $e->errors()['quantities'][0]);
        }

        // La transacción se revierte: el pedido original sigue intacto.
        $this->assertSame(1, $expensive->fresh()->reserved);
        $this->assertSame(0, $cheap->fresh()->reserved);
        $this->assertSame('20.00', $reservation->fresh()->total);
    }

    public function test_customer_can_cancel_only_unpaid_reservations(): void
    {
        $offering = $this->offering(['stock' => 5]);
        $unpaid = $this->reserve([$offering->id => 2]);
        $paid = $this->reserve([$offering->id => 1]);
        $this->pay($paid, '3.00');

        $this->service->cancelByCustomer($unpaid);
        $this->assertTrue($unpaid->fresh()->isCancelled());
        $this->assertSame(1, $offering->fresh()->reserved);

        $this->expectExceptionMessage('puedes cambiar tu pedido');
        $this->service->cancelByCustomer($paid);
    }

    // ---------- Donación y vencimientos ----------

    public function test_donation_only_after_the_deadline_and_with_payments(): void
    {
        $user = User::factory()->create();
        $offering = $this->offering(['serve_date' => today()->addDay()->toDateString(), 'slot_start' => '12:00']);
        $reservation = $this->reserve([$offering->id => 1]);

        try {
            $this->service->markDonated($reservation, $user);
            $this->fail('Sin pagos no se dona.');
        } catch (ReservationException) {
        }

        $this->pay($reservation, '5.00');

        try {
            $this->service->markDonated($reservation, $user);
            $this->fail('Aún dentro del plazo de cambios.');
        } catch (ReservationException $e) {
            $this->assertStringContainsString('a tiempo de cambiar', $e->getMessage());
        }

        $this->travelTo(Carbon::parse(today()->addDay()->toDateString().' 08:00'));
        $this->service->markDonated($reservation, $user);

        $reservation->refresh();
        $this->assertTrue($reservation->isDonated());
        $this->assertSame($user->id, $reservation->donated_by);
        $this->assertSame(1, $offering->fresh()->reserved); // la comida se preparó: no vuelve al stock

        $this->expectException(ReservationException::class);
        $this->service->deliver($reservation, $user);
    }

    public function test_process_overdue_expires_unpaid_and_donates_unpicked_paid_orders(): void
    {
        $day = today()->addDay()->toDateString();
        $lunch = $this->offering(['serve_date' => $day, 'slot_start' => '12:00', 'slot_end' => '14:00', 'stock' => 10]);
        $dinner = $this->offering(['serve_date' => $day, 'slot_start' => '19:00', 'slot_end' => '21:00', 'stock' => 10]);

        $unpaidLunch = $this->reserve([$lunch->id => 3]);
        $paidLunch = $this->reserve([$lunch->id => 2]);
        $deliveredLunch = $this->reserve([$lunch->id => 1]);
        $paidDinner = $this->reserve([$dinner->id => 1]);

        $this->pay($paidLunch, '2.00');
        $this->pay($deliveredLunch, $deliveredLunch->total);
        $this->service->deliver($deliveredLunch, User::factory()->create());
        $this->pay($paidDinner, '1.00');

        $this->travelTo(Carbon::parse("$day 14:30"));
        $this->artisan('reservations:process')->assertSuccessful();

        $this->assertTrue($unpaidLunch->fresh()->isCancelled());
        $this->assertTrue($paidLunch->fresh()->isDonated());
        $this->assertTrue($deliveredLunch->fresh()->isDelivered());
        $this->assertSame(DeliveryStatus::Pending, $paidDinner->fresh()->delivery_status); // su horario no terminó
        $this->assertSame(3, $lunch->fresh()->reserved); // se liberaron las 3 sin pago
        $this->assertNull($paidLunch->fresh()->donated_by); // automático
    }

    // ---------- Cancelación por el personal ----------

    public function test_cancel_releases_stock_and_blocks_further_operations(): void
    {
        $user = User::factory()->create();
        $offering = $this->offering(['stock' => 5]);
        $reservation = $this->reserve([$offering->id => 4]);

        $this->service->cancel($reservation, $user, 'Pedido por el cliente');

        $this->assertSame(0, $offering->fresh()->reserved);
        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);

        foreach ([
            fn () => $this->service->cancel($reservation, $user, 'otra vez'),
            fn () => $this->pay($reservation, '1.00', $user),
            fn () => $this->service->deliver($reservation, $user),
            fn () => $this->service->change($reservation, [$offering->id => 1]),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Una reserva cancelada no debe admitir más operaciones.');
            } catch (ReservationException) {
                $this->assertSame(0, $offering->fresh()->reserved);
            }
        }
    }

    public function test_payments_and_status_changes_are_audited(): void
    {
        $cashier = User::factory()->create();
        $reservation = $this->reserve([$this->offering()->id => 1]);

        $this->actingAs($cashier);
        $this->pay($reservation, '5.00', $cashier);

        $paymentLog = AuditLog::where('auditable_type', (new ReservationPayment)->getMorphClass())->first();
        $this->assertSame($cashier->id, $paymentLog->user_id);
        $this->assertSame('5.00', $paymentLog->new_values['amount']);

        $created = AuditLog::where('action', 'created')->where('auditable_type', $reservation->getMorphClass())->first();
        $this->assertSame('[oculto]', $created->new_values['token_hash']);
    }
}
