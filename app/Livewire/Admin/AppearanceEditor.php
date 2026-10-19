<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\AppearanceForm;
use App\Models\SiteSetting;
use App\Support\Theme;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Apariencia')]
class AppearanceEditor extends Component
{
    public AppearanceForm $form;

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

        // Recarga completa: el tema nuevo va en el <head> de cada página.
        session()->flash('status', 'Apariencia actualizada.');
        $this->redirectRoute('admin.appearance');
    }

    /** Vuelve a los colores y fuentes de diseño (al guardar). */
    public function restoreDefaults(): void
    {
        $this->form->reset();
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.appearance-editor', [
            'fonts' => Theme::FONTS,
            'defaultColors' => Theme::DEFAULT_COLORS,
            'defaultFonts' => Theme::DEFAULT_FONTS,
        ]);
    }

    private function setting(): SiteSetting
    {
        return SiteSetting::query()->first() ?? new SiteSetting(SiteSetting::defaults());
    }
}
