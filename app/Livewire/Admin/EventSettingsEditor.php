<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\EventSettingsForm;
use App\Models\EventSetting;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Datos de la convención')]
class EventSettingsEditor extends Component
{
    public EventSettingsForm $form;

    public function boot(): void
    {
        $this->authorize('manage-content');
    }

    public function mount(): void
    {
        $this->form->fillFrom($this->setting());
    }

    public function save(): void
    {
        $this->setting()->fill($this->form->attributes())->save();

        session()->flash('status', 'Datos de la convención actualizados.');
    }

    public function render(): View
    {
        return view('livewire.admin.event-settings-editor');
    }

    private function setting(): EventSetting
    {
        return EventSetting::query()->first() ?? new EventSetting(EventSetting::defaults());
    }
}
