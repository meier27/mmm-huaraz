<?php

namespace App\Livewire\Forms;

class VerseForm extends ContentForm
{
    public string $reference = '';

    public string $text = '';

    public ?string $version = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    protected function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:100'],
            'text' => ['required', 'string', 'max:2000'],
            'version' => ['nullable', 'string', 'max:50'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
