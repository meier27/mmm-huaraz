<?php

namespace App\Livewire;

use App\Exceptions\DonationException;
use App\Models\ChurchEvent;
use App\Models\DonationMethod;
use App\Services\DonationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * El donante declara su aporte para que la organización lo verifique a mano.
 * No confirma nada por sí solo.
 */
class DonationReportForm extends Component
{
    use WithFileUploads;

    public string $donation_method_id = '';

    public string $declared_amount = '';

    public string $operation_number = '';

    public string $paid_on = '';

    public string $donor_name = '';

    public string $donor_phone = '';

    public string $donor_email = '';

    public string $message = '';

    /** @var TemporaryUploadedFile|null */
    public $receipt = null;

    public bool $consent = false;

    /** Código entregado tras registrar el aporte. */
    #[Locked]
    public ?string $reference = null;

    /** Si la constancia quedó adjunta (un archivo rechazado al subirlo no llega aquí). */
    #[Locked]
    public bool $receiptAttached = false;

    /** Evento desde cuya página se dona (opcional; no modificable desde el navegador). */
    #[Locked]
    public ?int $eventId = null;

    public function mount(?ChurchEvent $event = null): void
    {
        // Desde la página de un evento solo si el evento admite registrar aportes.
        abort_if($event?->exists && ! $event->donation_reports_enabled, 404);

        $this->eventId = $event?->id;
        $this->paid_on = today()->toDateString();
    }

    public function submit(DonationService $donations): void
    {
        // Por si el equipo lo desactivó mientras la página estaba abierta.
        abort_if($this->eventId && ! ChurchEvent::query()->whereKey($this->eventId)->value('donation_reports_enabled'), 404);

        $key = 'donation-report:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, config('donations.reports_per_hour'))) {
            throw ValidationException::withMessages(['donation_method_id' => 'Has registrado demasiados aportes seguidos. Inténtalo de nuevo más tarde.']);
        }

        $data = $this->validate([
            'donation_method_id' => ['required', 'integer', Rule::exists('donation_methods', 'id')->where('is_published', true)],
            'declared_amount' => ['required', 'decimal:0,2', 'min:1', 'max:100000'],
            'operation_number' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-\s]+$/'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:'.today()->subYear()->toDateString()],
            'donor_name' => ['nullable', 'string', 'max:150'],
            'donor_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s()-]{6,20}$/'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
            // Tipo real comprobado por contenido (mimes), no por la extensión.
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'consent' => ['accepted'],
        ], [
            'operation_number.regex' => 'Usa solo letras, números y guiones.',
            'consent.accepted' => 'Debes aceptar el uso de tus datos para verificar el aporte.',
            'paid_on.before_or_equal' => 'La fecha no puede ser futura.',
        ], [
            'donation_method_id' => 'método',
            'declared_amount' => 'monto',
            'operation_number' => 'n.º de operación',
            'paid_on' => 'fecha',
            'donor_name' => 'nombre',
            'donor_phone' => 'celular',
            'donor_email' => 'correo',
            'message' => 'mensaje',
            'receipt' => 'comprobante',
        ]);

        try {
            $donation = $donations->report(
                array_map(fn ($v) => is_string($v) && trim($v) === '' ? null : $v, $data),
                $this->receipt,
                request()->ip(),
                $this->eventId ? ChurchEvent::query()->published()->find($this->eventId) : null,
            );
        } catch (DonationException $e) {
            throw ValidationException::withMessages(['donation_method_id' => $e->getMessage()]);
        }

        RateLimiter::hit($key, 3600);

        $this->reset(['donation_method_id', 'declared_amount', 'operation_number', 'donor_name', 'donor_phone', 'donor_email', 'message', 'receipt', 'consent']);
        $this->paid_on = today()->toDateString();
        $this->reference = $donation->reference;
        $this->receiptAttached = $donation->receipt_path !== null;
    }

    public function another(): void
    {
        $this->reference = null;
    }

    public function render(): View
    {
        return view('livewire.donation-report-form', [
            'methods' => DonationMethod::query()->published()->ordered()->get(['id', 'label', 'type']),
        ]);
    }
}
