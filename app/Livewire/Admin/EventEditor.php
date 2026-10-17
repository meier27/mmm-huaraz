<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\ChurchEventForm;
use App\Models\ChurchEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pestaña «Datos» de un evento: información, módulos activos y
 * configuración de la actividad de comidas.
 */
#[Layout('components.layouts.admin')]
class EventEditor extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $eventId;

    public ChurchEventForm $form;

    public function boot(): void
    {
        $this->authorize('manage-content');
    }

    public function mount(ChurchEvent $event): void
    {
        $this->eventId = $event->id;
        $this->form->fillFrom($event);
    }

    #[Computed]
    public function event(): ChurchEvent
    {
        return ChurchEvent::query()->findOrFail($this->eventId);
    }

    public function save(): void
    {
        $attributes = $this->form->attributes();
        $event = $this->event();

        DB::transaction(function () use ($event, $attributes) {
            // Solo un evento destacado en portada a la vez.
            if ($attributes['is_featured'] && ! $event->is_featured) {
                ChurchEvent::query()->where('is_featured', true)->whereKeyNot($event->id)->get()
                    ->each(fn (ChurchEvent $other) => $other->update(['is_featured' => false]));
            }

            $event->fill($attributes)->save();
        });

        if ($this->form->image) {
            $event->replaceImage($this->form->image);
            $this->form->image = null;
        }

        unset($this->event);
        session()->flash('status', 'Evento guardado.');
    }

    public function removeImage(): void
    {
        $this->event()->removeImage();
        unset($this->event);
    }

    public function render(): View
    {
        return view('livewire.admin.event-editor', [
            'modules' => ChurchEvent::MODULES,
        ])->title($this->event()->title);
    }
}
