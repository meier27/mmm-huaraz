<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\SiteSettingsForm;
use App\Models\SiteSetting;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Datos del sitio')]
class SiteSettingsEditor extends Component
{
    public SiteSettingsForm $form;

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

        session()->flash('status', 'Datos del sitio actualizados.');
    }

    public function render(): View
    {
        return view('livewire.admin.site-settings-editor', [
            'versions' => collect(config('bible.versions'))->map(fn (array $v) => $v['name'])->all(),
        ]);
    }

    private function setting(): SiteSetting
    {
        return SiteSetting::query()->first() ?? new SiteSetting(SiteSetting::defaults());
    }
}
