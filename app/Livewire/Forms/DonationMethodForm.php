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

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DonationMethodType::class)],
            'label' => ['required', 'string', 'max:150'],
            'holder_name' => ['nullable', 'string', 'max:150'],
            'account_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9\s-]+$/'],
            'cci' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\s-]+$/'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\s()-]+$/'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'is_published' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=3000,max_height=3000'],
        ];
    }
}
