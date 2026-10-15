<?php

namespace Tests\Feature\Reservations;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Livewire\ReservationForm;
use App\Livewire\ReservationStatusPanel;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\Concerns\CreatesOfferings;
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use CreatesOfferings, RefreshDatabase;

    private function reservation(int $quantity = 1): Reservation
    {
        return app(ReservationService::class)->create([$this->offering()->id => $quantity], 'Ana Prueba', '900000123', null, (string) Str::uuid());
    }

    public function test_reservation_page_says_closed_until_admin_opens_it(): void
    {
        $this->offering();

        $this->get('/reservas')->assertOk()->assertSee('Las reservas aún no están abiertas');
    }

    public function test_closed_reservations_cannot_be_submitted_even_by_crafted_requests(): void
    {
        $offering = $this->offering();

        Livewire::test(ReservationForm::class)
            ->set("quantities.{$offering->id}", 1)
            ->set('customer_name', 'Ana Prueba')
            ->set('customer_phone', '900000123')
            ->call('submit')
            ->assertHasErrors('quantities');

        $this->assertSame(0, Reservation::count());
    }

    public function test_visitor_reserves_and_is_redirected_to_the_qr_page(): void
    {
        $this->openReservations();
        $offering = $this->offering(['price' => '15.00']);

        $this->get('/reservas')->assertOk()->assertSee('Plato de prueba')->assertSee('S/ 15.00');

        Livewire::test(ReservationForm::class)
            ->set("quantities.{$offering->id}", 2)
            ->set('customer_name', 'Ana Prueba')
            ->set('customer_phone', '+51 900 000 123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(Reservation::sole()->publicUrl());

        $reservation = Reservation::sole();
        $this->assertSame('30.00', $reservation->total);

        $response = $this->get($reservation->publicUrl());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));

        $response
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee($reservation->code)
            ->assertSee('<svg', false)
            ->assertSee('Pendiente de pago')
            ->assertSee('•••••••123') // celular enmascarado
            ->assertDontSee('900 000 123');
    }

    public function test_validation_errors_for_customer_data(): void
    {
        $this->openReservations();
        $offering = $this->offering();

        Livewire::test(ReservationForm::class)
            ->set("quantities.{$offering->id}", 1)
            ->set('customer_name', '')
            ->set('customer_phone', 'llámame')
            ->call('submit')
            ->assertHasErrors(['customer_name' => 'required', 'customer_phone' => 'regex']);
    }

    public function test_reservation_creation_is_rate_limited(): void
    {
        $this->openReservations();
        config(['reservations.attempts_per_hour' => 2]);
        RateLimiter::clear('reservation-create:127.0.0.1');
        $offering = $this->offering(['stock' => 100]);

        $component = Livewire::test(ReservationForm::class)
            ->set("quantities.{$offering->id}", 1)
            ->set('customer_name', 'Ana Prueba')
            ->set('customer_phone', '900000123');

        $component->call('submit');
        Livewire::test(ReservationForm::class)->set("quantities.{$offering->id}", 1)->set('customer_name', 'Ana Prueba')->set('customer_phone', '900000123')->call('submit');

        Livewire::test(ReservationForm::class)
            ->set("quantities.{$offering->id}", 1)
            ->set('customer_name', 'Ana Prueba')
            ->set('customer_phone', '900000123')
            ->call('submit')
            ->assertHasErrors('quantities');

        $this->assertSame(2, Reservation::count());
    }

    public function test_invalid_or_malformed_tokens_return_404(): void
    {
        $this->get('/reserva/'.str_repeat('a', 48))->assertNotFound();
        $this->get('/reserva/1')->assertNotFound();
        $this->get('/reserva/'.str_repeat('a', 48).'/qr.svg')->assertNotFound();
    }

    public function test_qr_can_be_downloaded_as_svg(): void
    {
        $reservation = $this->reservation();

        $this->get(route('reservations.qr', $reservation->token_encrypted))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertHeader('Content-Disposition', 'attachment; filename="reserva-'.$reservation->code.'.svg"');
    }

    public function test_guest_sees_status_but_no_staff_actions(): void
    {
        $reservation = $this->reservation();

        $this->get($reservation->publicUrl())->assertOk()->assertDontSee('Acciones del personal');

        Livewire::test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->call('deliver')
            ->assertForbidden();
    }

    public function test_cashier_scanning_the_qr_can_verify_payment(): void
    {
        $reservation = $this->reservation();
        $cashier = User::factory()->role(Role::Cashier)->create();

        $this->actingAs($cashier)->get($reservation->publicUrl())->assertSee('Acciones del personal')->assertSee('Verificar pago');

        Livewire::actingAs($cashier)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('paymentMethod', 'yape')
            ->set('paymentReference', 'OP-987')
            ->call('verifyPayment')
            ->assertHasNoErrors()
            ->assertSee('Pago verificado');

        $this->assertTrue($reservation->fresh()->isPaid());
        $this->assertSame(PaymentMethod::Yape, $reservation->fresh()->payment_method);
    }

    public function test_delivery_role_cannot_verify_payments_or_cancel(): void
    {
        $reservation = $this->reservation();
        $delivery = User::factory()->role(Role::Delivery)->create();

        Livewire::actingAs($delivery)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->call('verifyPayment')
            ->assertForbidden();

        Livewire::actingAs($delivery)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('cancelReason', 'No vino')
            ->call('cancel')
            ->assertForbidden();

        $this->assertFalse($reservation->fresh()->isPaid());
    }

    public function test_delivery_role_registers_delivery_once_and_sees_clear_error_on_repeat(): void
    {
        $reservation = $this->reservation();
        app(ReservationService::class)->verifyPayment($reservation, User::factory()->create(), PaymentMethod::Cash, null);
        $delivery = User::factory()->role(Role::Delivery)->create();

        $panel = Livewire::actingAs($delivery)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->call('deliver')
            ->assertHasNoErrors();

        $this->assertTrue($reservation->fresh()->isDelivered());

        $panel->call('deliver')->assertHasErrors('action');
    }

    public function test_editor_role_has_no_reservation_powers(): void
    {
        $reservation = $this->reservation();

        Livewire::actingAs(User::factory()->role(Role::Editor)->create())
            ->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->call('verifyPayment')
            ->assertForbidden();
    }

    public function test_only_admin_can_cancel_a_paid_reservation(): void
    {
        $reservation = $this->reservation();
        app(ReservationService::class)->verifyPayment($reservation, User::factory()->create(), PaymentMethod::Cash, null);

        Livewire::actingAs(User::factory()->role(Role::Cashier)->create())
            ->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('cancelReason', 'Reembolso')
            ->call('cancel')
            ->assertForbidden();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('cancelReason', 'Reembolso acordado')
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertTrue($reservation->fresh()->isCancelled());
    }

    public function test_reservation_id_in_the_panel_cannot_be_tampered(): void
    {
        $reservation = $this->reservation();

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs(User::factory()->role(Role::Cashier)->create())
            ->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('reservationId', $reservation->id + 1);
    }
}
