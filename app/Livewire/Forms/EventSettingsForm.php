<?php

namespace App\Livewire\Forms;

class EventSettingsForm extends ContentForm
{
    public string $organization = '';

    public string $name = '';

    public string $theme = '';

    public string $city = '';

    public string $starts_on = '';

    public string $ends_on = '';

    public ?string $summary = '';

    public ?string $contact_phone = '';

    public ?string $contact_whatsapp = '';

    public ?string $contact_email = '';

    public ?string $facebook_url = '';

    public ?string $instagram_url = '';

    public ?string $youtube_url = '';

    public ?string $tiktok_url = '';

    public bool $reservations_open = false;

    public ?string $reservation_instructions = '';

    protected function rules(): array
    {
        $phone = ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'];
        $url = ['nullable', 'url:https', 'max:500'];

        return [
            'organization' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'theme' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'contact_phone' => $phone,
            'contact_whatsapp' => $phone,
            'contact_email' => ['nullable', 'email', 'max:255'],
            'facebook_url' => $url,
            'instagram_url' => $url,
            'youtube_url' => $url,
            'tiktok_url' => $url,
            'reservations_open' => ['boolean'],
            'reservation_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
