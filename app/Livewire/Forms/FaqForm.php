<?php

namespace App\Livewire\Forms;

class FaqForm extends ContentForm
{
    public string $question = '';

    public string $answer = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    protected function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
