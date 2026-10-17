<?php

namespace App\Livewire\Forms;

use App\Enums\DonationMethodType;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DonationMethodForm extends ContentForm
{
    protected array $except = ['qr_image'];

    public string $type = 'bank_transfer';

    public string $label = '';

    public ?string $holder_name = '';

    public ?string $account_number = '';

    public ?string $cci = '';

    public ?string $phone = '';

    public ?string $instructions = '';

    public bool $is_published = false;

    public int|string $sort_order = 0;

    /** @var TemporaryUploadedFile|null */
    public $qr_image = null;

    /**
     * Campos que corresponden a cada tipo (los demás se vacían al guardar,
     * para que no se publiquen datos que el equipo ya no ve en el formulario).
     *
     * @return list<string>
     */
    public static function fieldsFor(string $type): array
    {
        return match ($type) {
            DonationMethodType::Yape->value, DonationMethodType::Plin->value => ['holder_name', 'phone', 'qr'],
            DonationMethodType::BankTransfer->value => ['holder_name', 'account_number', 'cci'],
            default => ['holder_name', 'account_number', 'cci', 'phone', 'instructions', 'qr'],
        };
    }

    protected function rules(): array
    {
        $wallet = in_array($this->type, [DonationMethodType::Yape->value, DonationMethodType::Plin->value], true);

        return [
            'type' => ['required', Rule::enum(DonationMethodType::class)],
            'label' => ['required', 'string', 'max:150'],
            'holder_name' => ['nullable', 'string', 'max:150'],
            'account_number' => [$this->type === DonationMethodType::BankTransfer->value ? 'required_without:cci' : 'nullable', 'nullable', 'string', 'max:50', 'regex:/^[0-9\s-]+$/'],
            'cci' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\s-]+$/'],
            'phone' => [$wallet ? 'required' : 'nullable', 'nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'phone.required' => 'Indica el número de celular asociado.',
            'account_number.required_without' => 'Indica el número de cuenta o el CCI.',
        ];
    }

    /** @return array<string, mixed> */
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $keep = self::fieldsFor($this->type);

        foreach (['holder_name', 'account_number', 'cci', 'phone', 'instructions'] as $field) {
            if (! in_array($field, $keep, true)) {
                $attributes[$field] = null;
            }
        }

        return $attributes;
    }
}
