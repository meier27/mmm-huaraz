<?php

namespace App\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LeaderForm extends ContentForm
{
    protected array $except = ['image'];

    public string $name = '';

    public string $role = '';

    public ?string $bio = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }
}
