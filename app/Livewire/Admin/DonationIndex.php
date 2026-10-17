<?php

namespace App\Livewire\Admin;

use App\Enums\DonationStatus;
use App\Exceptions\DonationException;
use App\Models\ChurchEvent;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
#[Title('Aportes')]
class DonationIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'pending')]
    public string $status = 'pending';

    #[Url(as: 'evento', except: '')]
    public string $event = '';

    #[Locked]
    public ?int $reviewingId = null;

    public string $verifiedAmount = '';

    public string $note = '';

    public function boot(): void
    {
        $this->authorize('manage-donations');
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['search', 'status', 'event'], true)) {
            $this->resetPage();
        }
    }

    public function review(int $id): void
    {
        $donation = Donation::query()->findOrFail($id);

        $this->resetValidation();
        $this->reviewingId = $donation->id;
        $this->verifiedAmount = (string) $donation->declared_amount;
        $this->note = '';
    }

    public function closeReview(): void
    {
        $this->reset(['reviewingId', 'verifiedAmount', 'note']);
        $this->resetValidation();
    }

    public function verify(DonationService $donations): void
    {
        $this->validate([
            'verifiedAmount' => ['required', 'decimal:0,2', 'min:0.01', 'max:100000'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [], ['verifiedAmount' => 'monto recibido', 'note' => 'nota']);

        $this->run(fn (Donation $d) => $donations->verify($d, auth()->user(), (string) $this->verifiedAmount, $this->note ?: null), 'Aporte verificado.');
    }

    public function reject(DonationService $donations): void
    {
        $this->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Indica el motivo del rechazo.'], ['note' => 'motivo']);

        $this->run(fn (Donation $d) => $donations->reject($d, auth()->user(), $this->note), 'Aporte rechazado.');
    }

    public function reopen(DonationService $donations): void
    {
        $this->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Indica por qué se reabre la revisión.'], ['note' => 'motivo']);

        $this->run(fn (Donation $d) => $donations->reopen($d, auth()->user(), $this->note), 'Aporte devuelto a «por verificar».');
    }

    private function run(callable $action, string $message): void
    {
        try {
            $action(Donation::query()->findOrFail($this->reviewingId));
        } catch (DonationException $e) {
            throw ValidationException::withMessages(['note' => $e->getMessage()]);
        }

        $this->closeReview();
        session()->flash('status', $message);
    }

    public function render(): View
    {
        $search = trim($this->search);

        $donations = Donation::query()
            ->with('reviewer:id,name')
            ->when(DonationStatus::tryFrom($this->status), fn (Builder $q, $s) => $q->where('status', $s))
            ->when(ctype_digit($this->event), fn (Builder $q) => $q->forEvent((int) $this->event))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('reference', strtoupper($search))
                ->orWhere('operation_number', $search)
                ->orWhere('donor_name', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->latest()
            ->paginate(20);

        $reviewing = $this->reviewingId ? Donation::query()->with(['reviewer:id,name', 'method'])->find($this->reviewingId) : null;

        return view('livewire.admin.donation-index', [
            'donations' => $donations,
            'reviewing' => $reviewing,
            'duplicates' => $reviewing?->duplicates()->get(['id', 'reference', 'status']) ?? collect(),
            'statuses' => DonationStatus::cases(),
            'events' => ChurchEvent::query()->where('has_donations', true)->orderByDesc('starts_on')->pluck('title', 'id'),
            'pendingCount' => Donation::query()->status(DonationStatus::Pending)->count(),
            'verifiedTotal' => Donation::query()->status(DonationStatus::Verified)->sum('verified_amount'),
        ]);
    }
}
