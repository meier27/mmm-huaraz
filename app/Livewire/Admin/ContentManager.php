<?php

namespace App\Livewire\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Listado + formulario de alta/edición para contenido administrable.
 * Cada subclase declara "public XxxForm $form" y el modelo que gestiona.
 *
 * La autorización se comprueba en cada petición (boot), no solo al montar,
 * y los registros se vuelven a consultar en el servidor antes de modificarlos.
 */
#[Layout('components.layouts.admin')]
abstract class ContentManager extends Component
{
    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    public function boot(): void
    {
        $this->authorize('manage-content');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $record = $this->findRecord($id);
        $this->form->fillFrom($record);
        $this->editingId = $record->getKey();
        $this->showForm = true;
    }

    public function save(): void
    {
        $attributes = $this->form->attributes();

        $record = $this->editingId ? $this->findRecord($this->editingId) : new ($this->modelClass());
        $record->fill($attributes);
        $this->beforeSave($record);
        $record->save();
        $this->afterSave($record);

        $this->resetForm();
        session()->flash('status', 'Cambios guardados.');
    }

    public function togglePublished(int $id): void
    {
        $record = $this->findRecord($id);
        $record->is_published = ! $record->is_published;
        $record->save();
    }

    public function delete(int $id): void
    {
        $this->findRecord($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('status', 'Registro eliminado.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    /** Ajustes derivados que no vienen del formulario (p. ej. el slug). */
    protected function beforeSave(Model $record): void
    {
        //
    }

    protected function afterSave(Model $record): void
    {
        //
    }

    protected function resetForm(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->editingId = null;
        $this->showForm = false;
    }

    /** Consulta base de los registros gestionados (los gestores de evento la acotan). */
    protected function query(): Builder
    {
        return $this->modelClass()::query();
    }

    protected function findRecord(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }
}
