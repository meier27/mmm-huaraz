<?php

namespace App\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TouristPlaceForm extends ContentForm
{
    protected array $except = ['image'];

    public string $name = '';

    public ?string $description = '';

    public ?string $distance_note = '';

    public ?string $image_alt = '';

    public ?string $maps_url = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'distance_note' => ['nullable', 'string', 'max:150'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url:http,https', 'max:500'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }
}
