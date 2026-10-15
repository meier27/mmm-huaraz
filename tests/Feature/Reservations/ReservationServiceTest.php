<?php

namespace Tests\Feature\Reservations;

use App\Enums\DeliveryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\ReservationException;
use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_it_creates_a_reservation_computing_amounts_on_the_server(): void
    {
        $a = $this->offering(['price' => '12.50']);
        $b = $this->offering(['price' => '7.30'], ['name' => 'Otro plato']);

        $reservation = $this->reserve([$a->id => 3, $b->id => 2]);

        $this->assertSame('52.10', $reservation->fresh()->total); // 37.50 + 14.60
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertSame(PaymentStatus::Pending, $reservation->payment_status);
        $this->assertSame(DeliveryStatus::Pending, $reservation->delivery_status);
        $this->assertNotNull($reservation->expires_at);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{8}$/', $reservation->code);
        $this->assertSame(3, $a->fresh()->reserved);
        $this->assertSame(2, $b->fresh()->reserved);
        $this->assertSame(['12.50', '7.30'], $reservation->items()->orderBy('id')->pluck('unit_price')->all());
    }

    public function test_token_is_stored_hashed_and_resolvable(): void
    {
        $reservation = $this->reserve([$this->offering()->id => 1]);
        $token = $reservation->token_encrypted;

        $this->assertMatchesRegularExpression('/^[a-f0-9]{48}$/', $token);
        $this->assertSame(hash('sha256', $token), $reservation->token_hash);
        $this->assertDatabaseMissing('reservations', ['token_encrypted' => $token]); // cifrado en BD
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
        $cases = [
            'plato sin publicar' => $this->offering([], ['is_published' => false]),
            'oferta inactiva' => $this->offering(['is_active' => false]),
            'fecha pasada' => $this->offering(['serve_date' => today()->subDay()->toDateString()]),
        ];

        foreach ($cases as $case => $offering) {
            try {
                $this->reserve([$offering->id => 1]);
                $this->fail("Debió rechazarse: $case");
            } catch (ValidationException) {
                $this->assertSame(0, $offering->fresh()->reserved, $case);
            }
        }
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

    public function test_full_lifecycle_pay_then_deliver(): void
    {
        $cashier = User::factory()->create();
        $reservation = $this->reserve([$this->offering()->id => 1]);

        $this->service->verifyPayment($reservation, $cashier, PaymentMethod::Yape, 'OP-123');
        $reservation->refresh();
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(PaymentStatus::Verified, $reservation->payment_status);
        $this->assertSame($cashier->id, $reservation->payment_verified_by);
        $this->assertNull($reservation->expires_at);

        $this->service->deliver($reservation, $cashier);
        $this->assertSame(DeliveryStatus::Delivered, $reservation->fresh()->delivery_status);
    }

    public function test_delivery_requires_verified_payment(): void
    {
        $reservation = $this->reserve([$this->offering()->id => 1]);

        $this->expectException(ReservationException::class);
        $this->service->deliver($reservation, User::factory()->create());
    }

    public function test_delivery_cannot_be_registered_twice(): void
    {
        $user = User::factory()->create();
        $reservation = $this->reserve([$this->offering()->id => 1]);
        $this->service->verifyPayment($reservation, $user, PaymentMethod::Cash, null);
        $this->service->deliver($reservation, $user);

        $this->expectExceptionMessage('ya fue entregada');
        $this->service->deliver($reservation, $user);
    }

    public function test_payment_cannot_be_verified_twice(): void
    {
        $user = User::factory()->create();
        $reservation = $this->reserve([$this->offering()->id => 1]);
        $this->service->verifyPayment($reservation, $user, PaymentMethod::Cash, null);

        $this->expectException(ReservationException::class);
        $this->service->verifyPayment($reservation, $user, PaymentMethod::Cash, null);
    }

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
            fn () => $this->service->verifyPayment($reservation, $user, PaymentMethod::Cash, null),
            fn () => $this->service->deliver($reservation, $user),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Una reserva cancelada no debe admitir más operaciones.');
            } catch (ReservationException) {
                $this->assertSame(0, $offering->fresh()->reserved);
            }
        }
    }

    public function test_delivered_reservation_cannot_be_cancelled(): void
    {
        $user = User::factory()->create();
        $reservation = $this->reserve([$this->offering()->id => 1]);
        $this->service->verifyPayment($reservation, $user, PaymentMethod::Cash, null);
        $this->service->deliver($reservation, $user);

        $this->expectException(ReservationException::class);
        $this->service->cancel($reservation, $user, 'tarde');
    }

    public function test_expire_overdue_cancels_only_unpaid_expired_reservations(): void
    {
        $offering = $this->offering(['stock' => 10]);
        $expired = $this->reserve([$offering->id => 3]);
        $paid = $this->reserve([$offering->id => 2]);
        $fresh = $this->reserve([$offering->id => 1]);

        $this->service->verifyPayment($paid, User::factory()->create(), PaymentMethod::Cash, null);
        $this->travel(config('reservations.hold_hours') + 1)->hours();
        $fresh->forceFill(['expires_at' => now()->addHour()])->save();

        $this->artisan('reservations:expire')->assertSuccessful();

        $this->assertSame(ReservationStatus::Cancelled, $expired->fresh()->status);
        $this->assertSame(ReservationStatus::Confirmed, $paid->fresh()->status);
        $this->assertSame(ReservationStatus::Pending, $fresh->fresh()->status);
        $this->assertSame(3, $offering->fresh()->reserved); // 2 + 1
    }

    public function test_status_changes_are_audited_with_the_acting_user(): void
    {
        $cashier = User::factory()->create();
        $reservation = $this->reserve([$this->offering()->id => 1]);

        $this->actingAs($cashier);
        $this->service->verifyPayment($reservation, $cashier, PaymentMethod::Plin, null);

        $log = AuditLog::where('auditable_type', $reservation->getMorphClass())->where('action', 'updated')->latest('id')->first();
        $this->assertSame($cashier->id, $log->user_id);
        $this->assertSame('verified', $log->new_values['payment_status']);
        $this->assertSame('[oculto]', AuditLog::where('action', 'created')->where('auditable_type', $reservation->getMorphClass())->first()->new_values['token_hash']);
    }
}
