<?php

namespace App\Livewire\Forms;

use App\Support\FlipBook;
use App\Support\GoogleMaps;
use Illuminate\Validation\Rule;

class SiteSettingsForm extends ContentForm
{
    /** El enlace de Maps solo sirve para sacar las coordenadas; no se guarda. */
    protected array $except = ['maps_link'];

    public string $church_name = '';

    public ?string $tagline = '';

    public ?string $welcome_text = '';

    public ?string $service_schedule = '';

    public ?string $address = '';

    public int|float|string|null $latitude = '';

    public int|float|string|null $longitude = '';

    public ?string $maps_link = '';

    public ?string $contact_phone = '';

    public ?string $contact_email = '';

    public string $bible_version = '';

    public ?string $bethel_channel_id = '';

    public ?string $doctrine_book_url = '';

    protected function rules(): array
    {
        $phone = ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'];

        return [
            'church_name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'welcome_text' => ['nullable', 'string', 'max:5000'],
            'service_schedule' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'maps_link' => ['nullable', 'url:http,https', 'max:500'],
            'contact_phone' => $phone,
            'contact_email' => ['nullable', 'email', 'max:255'],
            'bible_version' => ['required', Rule::in(array_keys(config('bible.versions')))],
            // Identificador de canal de YouTube: "UC" + 22 caracteres.
            'bethel_channel_id' => ['nullable', 'regex:/^UC[A-Za-z0-9_-]{22}$/'],
            // Enlace de FlipHTML5 o el código <iframe> que da la página (se guarda solo el enlace).
            'doctrine_book_url' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                if (filled($value) && ! FlipBook::url($value)) {
                    $fail('Pega el enlace del libro de FlipHTML5 (https://online.fliphtml5.com/…) o su código para insertar.');
                }
            }],
        ];
    }

    protected function messages(): array
    {
        return [
            'bethel_channel_id.regex' => 'Debe ser un identificador de canal de YouTube (empieza por «UC» y tiene 24 caracteres).',
        ];
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        if ($coordinates = GoogleMaps::coordinatesFromUrl($this->maps_link)) {
            [$this->latitude, $this->longitude] = $coordinates;
        }

        $attributes = parent::attributes();
        // Del código <iframe> pegado solo se guarda el enlace del libro.
        $attributes['doctrine_book_url'] = FlipBook::url($attributes['doctrine_book_url'] ?? null);

        return $attributes;
    }
}
