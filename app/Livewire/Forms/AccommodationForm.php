<?php

namespace App\Livewire\Forms;

use App\Enums\AccommodationType;
use Illuminate\Validation\Rule;

class AccommodationForm extends ContentForm
{
    public string $type = 'hotel';

    public string $name = '';

    public ?string $address = '';

    public ?string $phone = '';

    public ?string $price_range = '';

    public ?string $website_url = '';

    public ?string $maps_url = '';

    public ?string $notes = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(AccommodationType::class)],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'],
            'price_range' => ['nullable', 'string', 'max:100'],
            'website_url' => ['nullable', 'url:http,https', 'max:500'],
            'maps_url' => ['nullable', 'url:http,https', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
