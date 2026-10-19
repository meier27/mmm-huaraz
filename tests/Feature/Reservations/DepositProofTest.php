<?php

namespace Tests\Feature\Reservations;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Exceptions\ReservationException;
use App\Livewire\Admin\ReservationVerifier;
use App\Livewire\ReservationForm;
use App\Livewire\ReservationStatusPanel;
use App\Models\AuditLog;
use App\Models\DonationMethod;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\CreatesOfferings;
use Tests\TestCase;

/**
 * Adelanto fijo con captura: el cliente la adjunta al reservar, recibe un
 * código y el personal acepta o rechaza el adelanto desde el panel.
 */
class DepositProofTest extends TestCase
{
    use CreatesOfferings, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->foodEvent()->update(['deposit_amount' => '5.00']);
    }

    private function reserveWithProof(int $quantity = 2): Reservation
    {
        // 2 × 12.50 = 25.00
        return app(ReservationService::class)->create(
            [$this->offering()->id => $quantity], 'Ana Prueba', '900000123', null, (string) Str::uuid(),
            null, UploadedFile::fake()->image('adelanto.jpg', 600, 900),
        );
    }

    public function test_the_form_requires_the_deposit_proof(): void
    {
        $this->openReservations();
        $offering = $this->offering();

        Livewire::test(ReservationForm::class, ['event' => $this->foodEvent()])
            ->set("quantities.{$offering->id}", 1)
            ->set('customer_name', 'Ana Prueba')
            ->set('customer_phone', '900000123')
            ->call('submit')
            ->assertHasErrors(['deposit_proof' => 'required']);

        $this->assertSame(0, Reservation::count());
        $this->assertSame(0, $offering->fresh()->reserved);
    }

    public function test_the_proof_must_be_a_real_image(): void
    {
        $this->openReservations();
        $offering = $this->offering();

        Livewire::test(ReservationForm::class, ['event' => $this->foodEvent()])
            ->set("quantities.{$offering->id}", 1)
            ->set('customer_name', 'Ana Prueba')
            ->set('customer_phone', '900000123')
            ->set('deposit_proof', UploadedFile::fake()->create('pago.pdf', 50, 'application/pdf'))
            ->call('submit')
            ->assertHasErrors('deposit_proof');

        $this->assertSame(0, Reservation::count());
    }

    public function test_the_form_shows_the_fixed_deposit_and_published_payment_methods(): void
    {
        $this->openReservations();
        $this->offering();
        DonationMethod::create(['type' => 'yape', 'label' => 'Yape de la iglesia', 'phone' => '987654321', 'is_published' => true]);
        DonationMethod::create(['type' => 'plin', 'label' => 'Plin oculto', 'phone' => '911111111', 'is_published' => false]);

        $this->get($this->reservationsUrl())
            ->assertOk()
            ->assertSee('Paga tu adelanto')
            ->assertSee('S/ 5.00')
            ->assertSee('Yape de la iglesia')
            ->assertSee('987654321')
            ->assertDontSee('Plin oculto')
            ->assertSee('Sube la captura');
    }

    public function test_fixed_deposit_overrides_the_percentage_and_never_exceeds_the_total(): void
    {
        $event = $this->foodEvent();
        $event->update(['deposit_min_percent' => 50]);

        $this->assertSame('5.00', $event->minimumDeposit('25.00'));
        $this->assertSame('3.50', $event->minimumDeposit('3.50'));

        $event->update(['deposit_amount' => null]);
        $this->assertSame('12.50', $event->fresh()->minimumDeposit('25.00'));
    }

    public function test_cashier_accepts_the_deposit_and_the_reservation_is_confirmed(): void
    {
        $reservation = $this->reserveWithProof();
        $cashier = User::factory()->role(Role::Cashier)->create();
        $this->assertTrue($reservation->awaitsDepositReview());

        $this->actingAs($cashier)->get($reservation->publicUrl())->assertSee('Adelanto por verificar')->assertDontSee('Registrar adelanto');

        Livewire::actingAs($cashier)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('depositMethod', 'plin')
            ->call('approveDeposit')
            ->assertHasNoErrors()
            ->assertSee('Adelanto aceptado');

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(PaymentStatus::Partial, $reservation->payment_status);
        $this->assertSame('5.00', $reservation->amount_paid);
        $this->assertSame('20.00', $reservation->balance());
        $this->assertSame(PaymentMethod::Plin, $reservation->payments()->sole()->method);
        $this->assertSame($cashier->id, $reservation->payments()->sole()->received_by);
        $this->assertFalse($reservation->awaitsDepositReview());
    }

    public function test_a_deposit_cannot_be_accepted_twice_or_without_proof(): void
    {
        $cashier = User::factory()->role(Role::Cashier)->create();
        $service = app(ReservationService::class);
        $reservation = $this->reserveWithProof();

        $service->approveDeposit($reservation, $cashier, PaymentMethod::Yape);

        $this->expectException(ReservationException::class);
        $service->approveDeposit($reservation, $cashier, PaymentMethod::Yape);
    }

    public function test_reservation_without_proof_cannot_be_accepted_as_deposit(): void
    {
        $reservation = app(ReservationService::class)->create([$this->offering()->id => 1], 'Ana', '900000123', null, (string) Str::uuid());

        $this->expectException(ReservationException::class);
        app(ReservationService::class)->approveDeposit($reservation, User::factory()->role(Role::Cashier)->create(), PaymentMethod::Yape);
    }

    public function test_rejecting_the_proof_cancels_and_frees_the_stock(): void
    {
        $reservation = $this->reserveWithProof();
        $offering = $reservation->items()->sole()->offering;
        $cashier = User::factory()->role(Role::Cashier)->create();

        Livewire::actingAs($cashier)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->call('rejectDeposit')
            ->assertHasErrors('rejectReason')
            ->set('rejectReason', 'El pago no llegó a la cuenta')
            ->call('rejectDeposit')
            ->assertHasNoErrors();

        $this->assertTrue($reservation->fresh()->isCancelled());
        $this->assertSame(0, $offering->fresh()->reserved);
        $this->get($reservation->publicUrl())->assertSee('El pago no llegó a la cuenta');
    }

    public function test_delivery_role_cannot_accept_reject_or_see_the_proof(): void
    {
        $reservation = $this->reserveWithProof();
        $delivery = User::factory()->role(Role::Delivery)->create();

        Livewire::actingAs($delivery)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->call('approveDeposit')->assertForbidden();
        Livewire::actingAs($delivery)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('rejectReason', 'No')
            ->call('rejectDeposit')->assertForbidden();

        $this->actingAs($delivery)->get(route('admin.reservations.proof', $reservation))->assertForbidden();
        $this->assertFalse($reservation->fresh()->hasPayments());
    }

    public function test_proof_is_private_and_every_view_is_audited(): void
    {
        $reservation = $this->reserveWithProof();

        $this->get(route('admin.reservations.proof', $reservation))->assertRedirect(route('login'));
        $this->assertNull($reservation->toArray()['deposit_proof_path'] ?? null);

        $cashier = User::factory()->role(Role::Cashier)->create();
        $this->actingAs($cashier)->get(route('admin.reservations.proof', $reservation))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertTrue(AuditLog::where('action', 'deposit_proof_viewed')->where('user_id', $cashier->id)->exists());
    }

    public function test_failed_reservation_does_not_leave_the_proof_behind(): void
    {
        $offering = $this->offering(['stock' => 1]);

        try {
            app(ReservationService::class)->create([$offering->id => 5], 'Ana', '900000123', null, (string) Str::uuid(), null, UploadedFile::fake()->image('a.jpg'));
            $this->fail('Se esperaba falta de stock.');
        } catch (ValidationException) {
        }

        $this->assertSame([], Storage::disk('local')->allFiles('reservation-proofs'));
    }

    // ---------- Verificador de código ----------

    public function test_verifier_accepts_codes_typed_loosely_and_shows_the_state(): void
    {
        $reservation = $this->reserveWithProof();
        $cashier = User::factory()->role(Role::Cashier)->create();
        $loose = strtolower(implode(' ', str_split($reservation->code, 4)));

        $this->actingAs($cashier)->get(route('admin.reservations.verify'))->assertOk()->assertSee('Verificar código');

        Livewire::actingAs($cashier)->test(ReservationVerifier::class)
            ->set('code', $loose)
            ->call('verify')
            ->assertSet('code', $reservation->code)
            ->assertSee('Adelanto por verificar')
            ->assertSee('Ana Prueba');

        app(ReservationService::class)->approveDeposit($reservation, $cashier, PaymentMethod::Yape);

        Livewire::actingAs($cashier)->test(ReservationVerifier::class)
            ->set('code', $reservation->code)
            ->call('verify')
            ->assertSee('Código válido · Reserva confirmada')
            ->assertSee('Cobra el saldo de S/ 20.00');
    }

    public function test_verifier_flags_unknown_and_already_delivered_codes(): void
    {
        $cashier = User::factory()->role(Role::Cashier)->create();
        $reservation = $this->reserveWithProof(1);
        $service = app(ReservationService::class);
        $service->approveDeposit($reservation, $cashier, PaymentMethod::Yape);
        $service->registerPayment($reservation, $cashier, $reservation->fresh()->balance(), PaymentMethod::Cash, null);
        $service->deliver($reservation, $cashier);

        Livewire::actingAs($cashier)->test(ReservationVerifier::class)
            ->set('code', 'ZZZZ9999')
            ->call('verify')
            ->assertSee('Código no válido');

        Livewire::actingAs($cashier)->test(ReservationVerifier::class)
            ->set('code', $reservation->code)
            ->call('verify')
            ->assertSee('Ya fue entregada');
    }

    public function test_verifier_is_limited_to_reservation_roles(): void
    {
        $this->get(route('admin.reservations.verify'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->role(Role::Editor)->create())
            ->get(route('admin.reservations.verify'))
            ->assertForbidden();

        $this->actingAs(User::factory()->role(Role::Delivery)->create())
            ->get(route('admin.reservations.verify'))
            ->assertOk();
    }

    // ---------- Portada ----------

    public function test_home_highlights_the_upcoming_event_with_its_city(): void
    {
        $event = $this->foodEvent();
        $event->update(['starts_on' => today()->addDays(5)->toDateString(), 'ends_on' => today()->addDays(8)->toDateString()]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Próximo evento')
            ->assertSee($event->title)
            ->assertSee('· Huaraz')
            ->assertSee('Faltan 5 días');
    }
}
