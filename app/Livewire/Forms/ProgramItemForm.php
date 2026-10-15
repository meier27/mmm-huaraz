<?php

namespace App\Livewire\Forms;

use Illuminate\Database\Eloquent\Model;

class ProgramItemForm extends ContentForm
{
    public int|string|null $program_day_id = '';

    public int|string|null $venue_id = '';

    public string $starts_at = '';

    public ?string $ends_at = '';

    public string $title = '';

    public ?string $speaker = '';

    public ?string $description = '';

    public bool $is_published = false;

    protected function rules(): array
    {
        return [
            'program_day_id' => ['required', 'integer', 'exists:program_days,id'],
            'venue_id' => ['nullable', 'integer', 'exists:venues,id'],
            'starts_at' => ['required', 'date_format:H:i,H:i:s'],
            'ends_at' => ['nullable', 'date_format:H:i,H:i:s', 'after:starts_at'],
            'title' => ['required', 'string', 'max:150'],
            'speaker' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['boolean'],
        ];
    }

    public function fillFrom(Model $record): void
    {
        parent::fillFrom($record);

        // El campo <input type="time"> trabaja con HH:MM.
        $this->starts_at = substr($this->starts_at, 0, 5);
        $this->ends_at = $this->ends_at ? substr($this->ends_at, 0, 5) : '';
    }
}
