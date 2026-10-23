<?php

namespace App\Livewire\Forms;

use App\Enums\GameItemType;
use App\Models\GameItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class GameItemForm extends ContentForm
{
    public string $type = 'question';

    public string $prompt = '';

    public string $answer = '';

    /** Respuestas incorrectas de una pregunta (se guardan juntas en «options»). */
    public string $wrong_1 = '';

    public string $wrong_2 = '';

    public string $wrong_3 = '';

    public ?string $reference = '';

    public bool $is_published = true;

    public int|string $sort_order = 0;

    protected array $except = ['wrong_1', 'wrong_2', 'wrong_3'];

    protected function rules(): array
    {
        $question = $this->type === GameItemType::Question->value;

        return [
            'type' => ['required', Rule::enum(GameItemType::class)],
            'prompt' => ['required', 'string', 'max:255'],
            'answer' => $question
                ? ['required', 'string', 'max:120']
                // Solo letras: la palabra va letra por letra en la cuadrícula.
                : ['required', 'string', 'max:40', 'regex:/^[\pL ]+$/u', function ($attribute, $value, $fail) {
                    $length = mb_strlen(GameItem::gridWord($value));
                    if ($length < 3 || $length > 12) {
                        $fail('La palabra debe tener entre 3 y 12 letras.');
                    }
                }],
            'wrong_1' => [Rule::requiredIf($question), 'nullable', 'string', 'max:120', 'different:answer'],
            'wrong_2' => [Rule::requiredIf($question), 'nullable', 'string', 'max:120', 'different:answer', 'different:wrong_1'],
            'wrong_3' => [Rule::requiredIf($question), 'nullable', 'string', 'max:120', 'different:answer', 'different:wrong_1', 'different:wrong_2'],
            'reference' => ['required', 'string', 'max:100'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    protected function messages(): array
    {
        return [
            'answer.regex' => 'La palabra solo puede tener letras.',
            'wrong_1.required' => 'Escribe tres respuestas incorrectas.',
            'wrong_2.required' => 'Escribe tres respuestas incorrectas.',
            'wrong_3.required' => 'Escribe tres respuestas incorrectas.',
            '*.different' => 'Las respuestas deben ser distintas entre sí.',
        ];
    }

    protected function validationAttributes(): array
    {
        return ['prompt' => $this->type === GameItemType::Question->value ? 'pregunta' : 'pista', 'answer' => 'respuesta', 'reference' => 'cita bíblica'];
    }

    public function fillFrom(Model $record): void
    {
        parent::fillFrom($record);
        [$this->wrong_1, $this->wrong_2, $this->wrong_3] = array_pad(array_values($record->options ?? []), 3, '');
    }

    public function attributes(): array
    {
        $data = parent::attributes();
        $data['answer'] = trim($data['answer']);
        $data['options'] = $this->type === GameItemType::Question->value
            ? array_map('trim', [$this->wrong_1, $this->wrong_2, $this->wrong_3])
            : null;

        return $data;
    }
}
