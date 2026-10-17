<?php

namespace App\Livewire\Admin;

use App\Livewire\Forms\SocialLinksForm;
use App\Models\SiteSetting;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.admin')]
#[Title('Redes sociales')]
class SocialLinksEditor extends Component
{
    public SocialLinksForm $form;

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

        session()->flash('status', 'Redes sociales actualizadas.');
    }

    /** Quita una red (deja de mostrarse en la web al guardar). */
    public function clear(string $field): void
    {
        if (array_key_exists($field, SocialLinksForm::NETWORKS) || $field === 'contact_whatsapp') {
            $this->form->{$field} = '';
            $this->resetValidation("form.{$field}");
        }
    }

    public function render(): View
    {
        return view('livewire.admin.social-links-editor', [
            'networks' => SocialLinksForm::NETWORKS,
            'whatsappUrl' => $this->setting()->whatsappUrl(),
        ]);
    }

    private function setting(): SiteSetting
    {
        return SiteSetting::query()->first() ?? new SiteSetting(SiteSetting::defaults());
    }
}
