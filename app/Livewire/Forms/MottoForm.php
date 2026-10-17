<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MottoForm extends ContentForm
{
    protected array $except = ['image'];

    public int|string $year = '';

    public string $title = '';

    public ?string $description = '';

    public ?string $verse_text = '';

    public ?string $verse_reference = '';

    public ?string $image_alt = '';

    public bool $is_published = true;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:1960', 'max:2100', Rule::unique('mottos', 'year')->ignore($this->getComponent()->editingId)],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:3000'],
            'verse_text' => ['nullable', 'string', 'max:255'],
            'verse_reference' => ['nullable', 'required_with:verse_text', 'string', 'max:100'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'is_published' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,max_width=4000,max_height=4000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'year.unique' => 'Ya hay un lema para ese año.',
            'verse_reference.required_with' => 'Indica la cita del versículo (p. ej. Josué 24:15).',
        ];
    }
}
