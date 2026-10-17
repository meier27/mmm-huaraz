<?php

namespace App\Livewire\Forms;

use App\Enums\VenueKind;
use App\Support\GoogleMaps;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VenueForm extends ContentForm
{
    protected array $except = ['image'];

    public string $kind = 'main';

    public string $name = '';

    public ?string $address = '';

    public ?string $directions = '';

    public int|float|string|null $latitude = '';

    public int|float|string|null $longitude = '';

    public ?string $maps_url = '';

    public ?string $image_alt = '';

    /** @var TemporaryUploadedFile|null */
    public $image = null;

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
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:min_width=600,max_width=4000,max_height=4000'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Si se pegó un enlace de Google Maps y faltan las coordenadas, se toman de él.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        if (blank($this->latitude) && blank($this->longitude) && ($coordinates = GoogleMaps::coordinatesFromUrl($this->maps_url))) {
            [$this->latitude, $this->longitude] = $coordinates;
        }

        return parent::attributes();
    }
}
