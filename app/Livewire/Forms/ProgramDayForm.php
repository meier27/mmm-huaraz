<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;

class ProgramDayForm extends ContentForm
{
    public string $date = '';

    public ?string $title = '';

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', Rule::unique('program_days', 'date')->ignore($this->getComponent()->editingId)],
            'title' => ['nullable', 'string', 'max:150'],
        ];
    }
}
