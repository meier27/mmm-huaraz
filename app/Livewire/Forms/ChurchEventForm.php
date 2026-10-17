<?php

namespace App\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ChurchEventForm extends ContentForm
{
    protected array $except = ['image'];

    public string $title = '';

    public ?string $organization = '';

    public ?string $theme = '';

    public ?string $city = '';

    public ?string $starts_on = '';

    public ?string $ends_on = '';

    public ?string $time_label = '';

    public ?string $location = '';

    public ?string $summary = '';

    public ?string $body = '';

    public ?string $image_alt = '';

    public ?string $hero_text = '';

    public ?string $hero_button_label = '';

    public ?string $hero_button_url = '';

    public bool $hero_show_title = true;

    public bool $hero_show_buttons = true;

    public bool $donation_reports_enabled = false;

    public ?string $external_url = '';

    public bool $is_published = false;

    public bool $is_featured = false;

    // Módulos (ChurchEvent::MODULES)
    public bool $has_program = false;

    public bool $has_venues = false;

    public bool $has_food = false;

    public bool $has_donations = false;

    public bool $has_accommodations = false;

    public bool $has_tourism = false;

    // Actividad de comidas
    public bool $reservations_open = false;

    public ?string $reservations_title = '';

    public ?string $reservation_instructions = '';

    public int|string|null $deposit_min_percent = '';

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'organization' => ['nullable', 'string', 'max:150'],
            'theme' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            // La fecha de fin solo tiene sentido con fecha de inicio.
            'ends_on' => blank($this->starts_on) ? ['nullable', 'prohibited'] : ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'time_label' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:20000'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'hero_text' => ['nullable', 'string', 'max:255'],
            'hero_button_label' => ['nullable', 'required_with:hero_button_url', 'string', 'max:50'],
            // Ruta interna ("/eventos/...") o URL https externa.
            'hero_button_url' => ['nullable', 'required_with:hero_button_label', 'string', 'max:500', 'regex:#^(/[A-Za-z0-9/_\-]*|https://\S+)$#'],
            'hero_show_title' => ['boolean'],
            'hero_show_buttons' => ['boolean'],
            'donation_reports_enabled' => ['boolean'],
            'external_url' => ['nullable', 'url:https', 'max:500'],
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'has_program' => ['boolean'],
            'has_venues' => ['boolean'],
            'has_food' => ['boolean'],
            'has_donations' => ['boolean'],
            'has_accommodations' => ['boolean'],
            'has_tourism' => ['boolean'],
            // No se pueden abrir reservas sin el módulo de comidas.
            'reservations_open' => ['boolean', 'declined_if:has_food,false'],
            'reservations_title' => ['nullable', 'string', 'max:150'],
            'reservation_instructions' => ['nullable', 'string', 'max:2000'],
            'deposit_min_percent' => ['nullable', 'integer', 'min:1', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'ends_on.prohibited' => 'Indica primero la fecha de inicio.',
            'hero_button_url.regex' => 'Usa una ruta interna (p. ej. /eventos/mi-evento/programa) o una dirección que empiece por https://.',
            'reservations_open.declined_if' => 'Activa el módulo «Comidas con reserva» para abrir las reservas.',
        ];
    }
}
