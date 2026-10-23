<?php

namespace App\Livewire\Forms;

use App\Enums\PrizeGoal;
use App\Services\GameCatalog;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PrizeForm extends ContentForm
{
    protected array $except = ['image'];

    public string $title = '';

    public ?string $description = '';

    public string $goal_type = 'stars';

    public ?string $goal_game = '';

    public int|string $goal_value = 100;

    public int|string $stock = 1;

    public ?string $image_alt = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    protected function rules(): array
    {
        $perGame = $this->goal_type === PrizeGoal::GameLevels->value;
        $max = match ($this->goal_type) {
            PrizeGoal::Stars->value => GameCatalog::LEVELS * 3 * count(GameCatalog::GAMES),
            PrizeGoal::Levels->value => GameCatalog::LEVELS * count(GameCatalog::GAMES),
            PrizeGoal::Weekly->value => 1,
            default => GameCatalog::LEVELS,
        };

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'goal_type' => ['required', Rule::enum(PrizeGoal::class)],
            'goal_game' => [Rule::requiredIf($perGame), 'nullable', Rule::in(array_keys(GameCatalog::GAMES))],
            'goal_value' => ['required', 'integer', 'min:1', 'max:'.$max],
            'stock' => ['required', 'integer', 'min:0', 'max:10000'],
            'image_alt' => ['nullable', 'required_with:image', 'string', 'max:255'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    protected function messages(): array
    {
        return ['goal_game.required' => 'Elige el juego de la meta.', 'goal_value.max' => 'Esa meta no se puede alcanzar (el máximo es :max).'];
    }

    protected function validationAttributes(): array
    {
        return ['title' => 'nombre del premio', 'goal_value' => 'meta', 'stock' => 'cantidad'];
    }

    public function attributes(): array
    {
        $data = parent::attributes();
        $data['goal_game'] = $this->goal_type === PrizeGoal::GameLevels->value ? $data['goal_game'] : null;
        $data['goal_value'] = $this->goal_type === PrizeGoal::Weekly->value ? 1 : $data['goal_value'];

        return $data;
    }
}
