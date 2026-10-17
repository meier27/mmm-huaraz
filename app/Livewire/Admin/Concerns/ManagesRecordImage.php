<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Database\Eloquent\Model;
use Livewire\WithFileUploads;

/**
 * Para gestores (ContentManager) cuyo modelo usa HasPublicImage y cuyo
 * formulario tiene una propiedad "image".
 */
trait ManagesRecordImage
{
    use WithFileUploads;

    protected function afterSave(Model $record): void
    {
        if ($this->form->image) {
            $record->replaceImage($this->form->image);
        }
    }

    public function removeImage(int $id): void
    {
        $this->findRecord($id)->removeImage();
    }
}
