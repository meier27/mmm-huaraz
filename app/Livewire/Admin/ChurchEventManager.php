<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\ChurchEventForm;
use App\Models\ChurchEvent;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

/**
 * Listado de eventos. Se crean con los datos básicos y se completan (módulos,
 * programa, comidas...) en la página de cada evento (EventEditor).
 */
#[Title('Eventos')]
class ChurchEventManager extends ContentManager
{
    use WithPagination;

    public ChurchEventForm $form;

    protected function modelClass(): string
    {
        return ChurchEvent::class;
    }

    public function save(): void
    {
        $event = new ChurchEvent($this->form->attributes());
        $event->slug = ChurchEvent::uniqueSlug($event->title);
        $event->save();

        session()->flash('status', 'Evento creado. Completa sus datos y activa los módulos que necesites.');
        $this->redirectRoute('admin.events.edit', $event, navigate: true);
    }

    public function edit(int $id): void
    {
        $this->redirectRoute('admin.events.edit', $this->findRecord($id), navigate: true);
    }

    /** Un evento con contenido (programa, platos, reservas...) no se borra: se despublica. */
    public function delete(int $id): void
    {
        /** @var ChurchEvent $event */
        $event = $this->findRecord($id);

        $inUse = collect(['programDays', 'venues', 'dishes', 'accommodations', 'touristPlaces', 'reservations', 'donations'])
            ->contains(fn (string $relation) => $event->{$relation}()->exists());

        if ($inUse) {
            $this->addError('delete', "«{$event->title}» tiene contenido asociado (programa, platos, reservas…): despublícalo en lugar de eliminarlo.");

            return;
        }

        parent::delete($id);
    }

    public function render(): View
    {
        return view('livewire.admin.church-event-manager', [
            'records' => ChurchEvent::query()->orderByDesc('starts_on')->orderByDesc('id')->paginate(20),
        ]);
    }
}
