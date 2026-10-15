<?php

namespace Tests\Feature\Reservations;

use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Livewire\ReservationChangeForm;
use App\Livewire\ReservationCustomerActions;
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
            ->assertSee('Pendiente de adelanto')
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

    public function test_cashier_scanning_the_qr_registers_advance_then_balance(): void
    {
        $reservation = $this->reservation(2); // 2 × 12.50 = 25.00
        $cashier = User::factory()->role(Role::Cashier)->create();

        $this->actingAs($cashier)->get($reservation->publicUrl())->assertSee('Acciones del personal')->assertSee('Registrar adelanto');

        $panel = Livewire::actingAs($cashier)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->assertSet('paymentAmount', '25.00')
            ->set('paymentAmount', '10')
            ->set('paymentMethod', 'yape')
            ->set('paymentReference', 'OP-987')
            ->call('registerPayment')
            ->assertHasNoErrors()
            ->assertSee('Pago registrado')
            ->assertSet('paymentAmount', '15.00')
            ->assertSee('Cobrar saldo');

        $this->assertSame('10.00', $reservation->fresh()->amount_paid);
        $this->assertSame(PaymentMethod::Yape, $reservation->payments()->first()->method);

        $panel->call('deliver')->assertHasErrors('action'); // falta el saldo

        $panel->set('paymentMethod', 'cash')->call('registerPayment')->assertHasNoErrors();
        $panel->call('deliver')->assertHasNoErrors();

        $this->assertTrue($reservation->fresh()->isDelivered());
    }

    public function test_payment_amount_is_validated(): void
    {
        $reservation = $this->reservation();

        Livewire::actingAs(User::factory()->role(Role::Cashier)->create())
            ->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('paymentAmount', 'abc')
            ->call('registerPayment')
            ->assertHasErrors('paymentAmount')
            ->set('paymentAmount', '999')
            ->call('registerPayment')
            ->assertHasErrors('action'); // supera el saldo

        $this->assertSame('0.00', $reservation->fresh()->amount_paid);
    }

    public function test_delivery_role_cannot_register_payments_donate_or_cancel(): void
    {
        $reservation = $this->reservation();
        $delivery = User::factory()->role(Role::Delivery)->create();

        foreach (['registerPayment', 'markDonated'] as $action) {
            Livewire::actingAs($delivery)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
                ->call($action)
                ->assertForbidden();
        }

        Livewire::actingAs($delivery)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->set('cancelReason', 'No vino')
            ->call('cancel')
            ->assertForbidden();

        $this->assertFalse($reservation->fresh()->hasPayments());
    }

    public function test_delivery_role_registers_delivery_once_and_sees_clear_error_on_repeat(): void
    {
        $reservation = $this->reservation();
        app(ReservationService::class)->registerPayment($reservation, User::factory()->create(), $reservation->total, PaymentMethod::Cash, null);
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
            ->call('registerPayment')
            ->assertForbidden();
    }

    public function test_only_admin_can_cancel_a_reservation_with_payments(): void
    {
        $reservation = $this->reservation();
        app(ReservationService::class)->registerPayment($reservation, User::factory()->create(), '5.00', PaymentMethod::Cash, null);

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

    public function test_cashier_marks_unpicked_order_as_donation_after_deadline(): void
    {
        $reservation = $this->reservation();
        app(ReservationService::class)->registerPayment($reservation, User::factory()->create(), '5.00', PaymentMethod::Cash, null);
        $cashier = User::factory()->role(Role::Cashier)->create();

        $this->actingAs($cashier)->get($reservation->publicUrl())->assertDontSee('Registrar como donación');

        $this->travelTo($reservation->load('items.offering')->changeDeadline()->addMinute());

        $this->actingAs($cashier)->get($reservation->publicUrl())->assertSee('Registrar como donación');
        Livewire::actingAs($cashier)->test(ReservationStatusPanel::class, ['reservationId' => $reservation->id])
            ->call('markDonated')
            ->assertHasNoErrors()
            ->assertSee('Donado (no recogido)');
    }

    // ---------- Autoservicio del cliente ----------

    public function test_customer_sees_change_option_and_deadline_on_qr_page(): void
    {
        $reservation = $this->reservation();

        $this->get($reservation->publicUrl())
            ->assertSee('¿No podrás recogerlo?')
            ->assertSee('Cambiar mi pedido')
            ->assertSee('Cancelar reserva')
            ->assertSee('5 horas antes');
    }

    public function test_customer_changes_order_through_the_change_page(): void
    {
        $reservation = $this->reservation();
        $dinner = $this->offering(['slot_start' => '19:00', 'slot_end' => '21:00', 'price' => '14.00'], ['name' => 'Cena de prueba']);
        $token = $reservation->token_encrypted;
        $original = $reservation->items()->first()->dish_offering_id;

        $this->get(route('reservations.change', $token))->assertOk()->assertSee('Cena de prueba')->assertSee('Tu pedido actual');

        Livewire::test(ReservationChangeForm::class, ['token' => $token])
            ->assertSet("quantities.$original", 1)
            ->set("quantities.$original", 0)
            ->set("quantities.{$dinner->id}", 1)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('reservations.show', $token));

        $this->assertSame('14.00', $reservation->fresh()->total);
        $this->get($reservation->publicUrl())->assertSee('Cena de prueba');
    }

    public function test_change_page_redirects_after_deadline(): void
    {
        $reservation = $this->reservation();
        $this->travelTo($reservation->load('items.offering')->changeDeadline());

        Livewire::test(ReservationChangeForm::class, ['token' => $reservation->token_encrypted])
            ->assertRedirect(route('reservations.show', $reservation->token_encrypted));

        $this->get($reservation->publicUrl())->assertSee('El plazo para cambiar este pedido terminó')->assertDontSee('Cambiar mi pedido');
    }

    public function test_change_page_rejects_invalid_tokens(): void
    {
        $this->get('/reserva/'.str_repeat('b', 48).'/cambiar')->assertNotFound();
    }

    public function test_customer_cancels_unpaid_reservation_from_qr_page(): void
    {
        $reservation = $this->reservation(3);
        $offering = $reservation->items()->first()->offering;

        Livewire::test(ReservationCustomerActions::class, ['token' => $reservation->token_encrypted])
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertTrue($reservation->fresh()->isCancelled());
        $this->assertSame(0, $offering->fresh()->reserved);
    }

    public function test_customer_cannot_cancel_after_paying(): void
    {
        $reservation = $this->reservation();
        app(ReservationService::class)->registerPayment($reservation, User::factory()->create(), '5.00', PaymentMethod::Cash, null);

        $this->get($reservation->publicUrl())->assertSee('Cambiar mi pedido')->assertDontSee('Cancelar reserva');

        Livewire::test(ReservationCustomerActions::class, ['token' => $reservation->token_encrypted])
            ->call('cancel')
            ->assertHasErrors('customer');

        $this->assertFalse($reservation->fresh()->isCancelled());
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
