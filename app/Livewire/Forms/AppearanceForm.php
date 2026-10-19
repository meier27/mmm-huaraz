<?php

namespace App\Livewire\Forms;

use App\Support\Theme;
use Illuminate\Validation\Rule;

class AppearanceForm extends ContentForm
{
    public ?string $color_primary = '';

    public ?string $color_accent = '';

    public ?string $color_button = '';

    public ?string $color_background = '';

    public ?string $color_gradient_end = '';

    public ?string $font_body = '';

    public ?string $font_heading = '';

    protected function rules(): array
    {
        $hex = ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'];
        // Texto blanco encima: se exige contraste legible (WCAG AA).
        $readableWithWhite = function (string $attribute, mixed $value, \Closure $fail) {
            if (Theme::validHex($value) && Theme::contrastWithWhite($value) < Theme::MIN_CONTRAST_WITH_WHITE) {
                $fail('Es demasiado claro: el texto blanco no se leería bien (contraste '.Theme::contrastWithWhite($value).':1, mínimo '.Theme::MIN_CONTRAST_WITH_WHITE.':1). Elige un tono más oscuro.');
            }
        };

        $rules = ['color_accent' => $hex, 'color_background' => $hex];

        foreach (Theme::NEEDS_WHITE_TEXT as $field) {
            $rules[$field] = [...$hex, $readableWithWhite];
        }

        return $rules + [
            'font_body' => ['nullable', Rule::in(array_keys(Theme::FONTS))],
            'font_heading' => ['nullable', Rule::in(array_keys(Theme::FONTS))],
        ];
    }

    protected function messages(): array
    {
        return ['regex' => 'Usa un color en formato #RRGGBB (p. ej. #39312d).'];
    }

    /** Guarda los colores en minúsculas para comparar y mostrar de forma uniforme. */
    public function attributes(): array
    {
        return array_map(fn ($value) => is_string($value) && str_starts_with($value, '#') ? strtolower($value) : $value, parent::attributes());
    }
}
