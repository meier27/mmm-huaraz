<?php

namespace Database\Seeders;

use App\Enums\VenueKind;
use App\Models\EventSetting;
use App\Models\ProgramDay;
use App\Models\Venue;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Siembra únicamente datos verificados en pe.mmmoficial.org (ver
 * config/convention.php). Es idempotente: no sobrescribe lo editado
 * desde el panel.
 */
class ConventionSeeder extends Seeder
{
    public function run(): void
    {
        if (! EventSetting::query()->exists()) {
            EventSetting::create(EventSetting::defaults());
        }

        Venue::firstOrCreate(
            ['name' => config('convention.venue')],
            ['kind' => VenueKind::Main, 'is_published' => true, 'sort_order' => 1],
        );

        $period = CarbonPeriod::create(config('convention.starts_at'), config('convention.ends_at'));

        foreach ($period as $date) {
            ProgramDay::firstOrCreate(['date' => Carbon::parse($date)->toDateString()]);
        }
    }
}
