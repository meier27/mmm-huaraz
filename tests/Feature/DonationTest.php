<?php

namespace Tests\Feature;

use App\Enums\DonationMethodType;
use App\Enums\DonationStatus;
use App\Enums\Role;
use App\Exceptions\DonationException;
use App\Livewire\Admin\DonationIndex;
use App\Livewire\DonationReportForm;
use App\Models\AuditLog;
use App\Models\ChurchEvent;
use App\Models\Donation;
use App\Models\DonationMethod;
use App\Models\User;
use App\Services\DonationService;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;

    private DonationMethod $method;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->method = DonationMethod::create([
            'type' => DonationMethodType::Yape, 'label' => 'Yape de prueba', 'phone' => '000 000 000', 'is_published' => true,
        ]);
    }

    private function fill(array $overrides = []): Testable
    {
        $component = Livewire::test(DonationReportForm::class);

        foreach ($overrides + [
            'donation_method_id' => (string) $this->method->id,
            'declared_amount' => '50',
            'operation_number' => '12345678',
            'paid_on' => today()->toDateString(),
            'consent' => true,
        ] as $field => $value) {
            $component->set($field, $value);
        }

        return $component;
    }

    private function donation(array $overrides = []): Donation
    {
        return app(DonationService::class)->report($overrides + [
            'donation_method_id' => $this->method->id, 'declared_amount' => '50.00',
            'operation_number' => '12345678', 'paid_on' => today()->toDateString(),
        ], null, '127.0.0.1');
    }

    public function test_by_default_the_page_only_lists_the_methods(): void
    {
        $event = ChurchEvent::create(['title' => 'Campaña', 'slug' => 'campana', 'starts_on' => today()->addWeek()->toDateString(), 'is_published' => true, 'has_donations' => true]);

        $this->get(route('events.donations', $event))
            ->assertSee('Yape de prueba')->assertSee('000 000 000')
            ->assertDontSee('Registra tu aporte');
    }

    public function test_form_is_shown_only_when_enabled_and_there_are_published_methods(): void
    {
        $event = ChurchEvent::create(['title' => 'Campaña', 'slug' => 'campana', 'starts_on' => today()->addWeek()->toDateString(), 'is_published' => true, 'has_donations' => true, 'donation_reports_enabled' => true]);
        $url = route('events.donations', $event);

        $this->get($url)->assertSee('Registra tu aporte');

        $this->method->update(['is_published' => false]);

        $this->get($url)->assertDontSee('Registra tu aporte');
    }

    public function test_donor_registers_an_anonymous_pending_donation_with_private_receipt(): void
    {
        $this->fill(['receipt' => UploadedFile::fake()->image('constancia.jpg', 600, 900)])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('¡Gracias por tu aporte!')
            ->assertSee('Constancia adjunta.');

        $donation = Donation::sole();
        $this->assertSame(DonationStatus::Pending, $donation->status);
        $this->assertNull($donation->donor_name);
        $this->assertNull($donation->verified_amount);
        $this->assertSame('50.00', (string) $donation->declared_amount);
        $this->assertSame('Yape de prueba', $donation->method_label);
        $this->assertMatchesRegularExpression('/^AP-[A-Z2-9]{7}$/', $donation->reference);
        $this->assertNotNull($donation->consent_at);

        // Privado, con nombre aleatorio, y nunca en el disco público.
        Storage::disk('local')->assertExists($donation->receipt_path);
        $this->assertStringNotContainsString('constancia', $donation->receipt_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_report_validation_rules(): void
    {
        $this->fill(['consent' => false])->call('submit')->assertHasErrors(['consent']);
        $this->fill(['paid_on' => today()->addDay()->toDateString()])->call('submit')->assertHasErrors(['paid_on']);
        $this->fill(['declared_amount' => '10.555'])->call('submit')->assertHasErrors(['declared_amount']);
        $this->fill(['operation_number' => '<script>'])->call('submit')->assertHasErrors(['operation_number']);

        $this->assertSame(0, Donation::count());
    }

    public function test_non_image_receipts_are_rejected_on_upload_and_never_stored(): void
    {
        foreach ([
            UploadedFile::fake()->create('constancia.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
        ] as $file) {
            // Livewire lo rechaza al subirlo (config livewire.temporary_file_upload.rules).
            $component = $this->fill(['receipt' => $file])->assertHasErrors(['receipt'])->assertSet('receipt', null);

            // Si el donante envía igual, el aporte se registra pero se le dice que va sin constancia.
            $component->call('submit')->assertHasNoErrors()->assertSee('Sin constancia adjunta');
        }

        $this->assertSame(2, Donation::whereNull('receipt_path')->count());
    }

    public function test_unpublished_method_is_rejected_on_the_server(): void
    {
        $hidden = DonationMethod::create(['type' => DonationMethodType::Yape, 'label' => 'Oculto', 'is_published' => false]);

        $this->fill(['donation_method_id' => (string) $hidden->id])->call('submit')->assertHasErrors(['donation_method_id']);

        $this->expectException(DonationException::class);
        $this->donation(['donation_method_id' => $hidden->id]);
    }

    public function test_reports_are_rate_limited_per_ip(): void
    {
        config(['donations.reports_per_hour' => 2]);

        $this->fill()->call('submit')->assertHasNoErrors();
        $this->fill()->call('submit')->assertHasNoErrors();
        $this->fill()->call('submit')->assertHasErrors(['donation_method_id']);

        $this->assertSame(2, Donation::count());
    }

    public function test_donation_fields_are_not_mass_assignable(): void
    {
        $this->expectException(MassAssignmentException::class);
        Donation::create(['status' => DonationStatus::Verified]);
    }

    public function test_only_admins_and_treasury_can_review_donations(): void
    {
        foreach ([Role::Admin, Role::Cashier] as $role) {
            $this->actingAs(User::factory()->role($role)->create())->get('/admin/aportes')->assertOk();
        }

        foreach ([Role::Editor] as $role) {
            $this->actingAs(User::factory()->role($role)->create())->get('/admin/aportes')->assertForbidden();
            Livewire::actingAs(User::factory()->role($role)->create())->test(DonationIndex::class)->assertForbidden();
        }
    }

    public function test_cashier_verifies_with_the_amount_actually_received(): void
    {
        $donation = $this->donation();
        $cashier = User::factory()->role(Role::Cashier)->create();

        Livewire::actingAs($cashier)->test(DonationIndex::class)
            ->call('review', $donation->id)
            ->assertSet('verifiedAmount', '50.00')
            ->set('verifiedAmount', '45.50')
            ->call('verify')
            ->assertHasNoErrors();

        $donation->refresh();
        $this->assertSame(DonationStatus::Verified, $donation->status);
        $this->assertSame('50.00', (string) $donation->declared_amount); // lo declarado no se toca
        $this->assertSame('45.50', (string) $donation->verified_amount);
        $this->assertSame($cashier->id, $donation->reviewed_by);
        $this->assertNotNull($donation->reviewed_at);
        $this->assertTrue(AuditLog::where('auditable_type', $donation->getMorphClass())->where('action', 'updated')->where('user_id', $cashier->id)->exists());
    }

    public function test_a_donation_cannot_be_reviewed_twice_and_can_be_reopened_with_a_reason(): void
    {
        $donation = $this->donation();
        $admin = User::factory()->admin()->create();
        $service = app(DonationService::class);

        $service->verify($donation, $admin, '50.00');

        try {
            $service->reject($donation, $admin, 'tarde');
            $this->fail('Se esperaba DonationException.');
        } catch (DonationException) {
        }

        Livewire::actingAs($admin)->test(DonationIndex::class)
            ->call('review', $donation->id)
            ->call('reopen')
            ->assertHasErrors(['note'])
            ->set('note', 'Monto mal registrado')
            ->call('reopen')
            ->assertHasNoErrors();

        $donation->refresh();
        $this->assertSame(DonationStatus::Pending, $donation->status);
        $this->assertNull($donation->verified_amount);
        $this->assertSame('Monto mal registrado', $donation->review_note);
    }

    public function test_rejection_requires_a_reason_and_verification_a_positive_amount(): void
    {
        $donation = $this->donation();
        $component = Livewire::actingAs(User::factory()->admin()->create())->test(DonationIndex::class)->call('review', $donation->id);

        $component->call('reject')->assertHasErrors(['note']);
        $component->set('verifiedAmount', '0')->call('verify')->assertHasErrors(['verifiedAmount']);
        $component->set('note', 'No figura en el estado de cuenta')->call('reject')->assertHasNoErrors();

        $this->assertSame(DonationStatus::Rejected, $donation->fresh()->status);
    }

    public function test_receipt_is_served_only_to_reviewers_and_each_view_is_audited(): void
    {
        $donation = app(DonationService::class)->report([
            'donation_method_id' => $this->method->id, 'declared_amount' => '20', 'operation_number' => '999', 'paid_on' => today()->toDateString(),
        ], UploadedFile::fake()->image('c.png', 300, 300), null);

        $url = route('admin.donations.receipt', $donation);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->role(Role::Editor)->create())->get($url)->assertForbidden();

        $cashier = User::factory()->role(Role::Cashier)->create();
        $this->actingAs($cashier)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $this->assertTrue(AuditLog::where('action', 'receipt_viewed')->where('auditable_id', $donation->id)->where('user_id', $cashier->id)->exists());

        $this->actingAs($cashier)->get(route('admin.donations.receipt', $this->donation()))->assertNotFound(); // sin constancia
    }

    public function test_reviewer_is_warned_about_repeated_operation_numbers(): void
    {
        $first = $this->donation();
        $this->donation(['operation_number' => '12345678']);
        $this->donation(['operation_number' => '87654321']);

        $this->assertSame(1, $first->duplicates()->count());

        Livewire::actingAs(User::factory()->admin()->create())->test(DonationIndex::class)
            ->call('review', $first->id)
            ->assertSee('mismo n.º de operación');
    }

    public function test_index_filters_by_status_and_searches_by_reference(): void
    {
        $pending = $this->donation(['donor_name' => 'Hermana Pendiente']);
        $verified = $this->donation(['donor_name' => 'Hermano Verificado', 'operation_number' => '111']);
        app(DonationService::class)->verify($verified, User::factory()->admin()->create(), '50');

        Livewire::actingAs(User::factory()->admin()->create())->test(DonationIndex::class)
            ->assertSee('Hermana Pendiente')->assertDontSee('Hermano Verificado')
            ->set('status', 'all')->assertSee('Hermano Verificado')
            ->set('search', strtolower($pending->reference))->assertSee('Hermana Pendiente')->assertDontSee('Hermano Verificado');
    }
}
