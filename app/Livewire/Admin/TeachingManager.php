<?php

namespace App\Livewire\Admin;

use App\Enums\TeachingCategory;
use App\Livewire\Admin\Concerns\ManagesRecordImage;
use App\Livewire\Forms\TeachingForm;
use App\Models\Teaching;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Title('Enseñanzas')]
class TeachingManager extends ContentManager
{
    use ManagesRecordImage, WithPagination;

    public TeachingForm $form;

    protected function modelClass(): string
    {
        return Teaching::class;
    }

    public function create(): void
    {
        parent::create();
        $this->form->published_on = today()->format('Y-m-d');
    }

    /**
     * El slug se fija al crear y no cambia al editar el título, para no
     * romper enlaces ya compartidos.
     *
     * @param  Teaching  $record
     */
    protected function beforeSave(Model $record): void
    {
        $record->video_id = $this->form->videoId();
        $record->slug ??= Teaching::uniqueSlug($record->title);
    }

    public function render(): View
    {
        return view('livewire.admin.teaching-manager', [
            'records' => Teaching::query()->latestFirst()->paginate(20),
            'editing' => $this->editingId ? Teaching::find($this->editingId) : null,
            'categories' => collect(TeachingCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
        ]);
    }
}
