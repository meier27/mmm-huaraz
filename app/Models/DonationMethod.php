<?php

namespace App\Models;

use App\Enums\DonationMethodType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasPublicImage;
use App\Models\Concerns\Publishable;
use Illuminate\Database\Eloquent\Model;

class DonationMethod extends Model
{
    use Auditable, HasPublicImage, Publishable;

    protected string $imageColumn = 'qr_image_path';

    protected $fillable = [
        'type', 'label', 'holder_name', 'account_number', 'cci', 'phone', 'instructions', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => DonationMethodType::class,
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
