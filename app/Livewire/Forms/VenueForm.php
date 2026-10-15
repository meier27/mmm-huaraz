<?php

namespace App\Livewire\Forms;

use App\Enums\VenueKind;
use Illuminate\Validation\Rule;

class VenueForm extends ContentForm
{
    public string $kind = 'main';

    public string $name = '';

    public ?string $address = '';

    public ?string $directions = '';

    public int|float|string|null $latitude = '';

    public int|float|string|null $longitude = '';

    public ?string $maps_url = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    protected function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(VenueKind::class)],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'directions' => ['nullable', 'string', 'max:5000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'maps_url' => ['nullable', 'url:http,https', 'max:500'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
