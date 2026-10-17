<?php

namespace Database\Seeders;

use App\Enums\VenueKind;
use App\Models\ChurchEvent;
use App\Models\ProgramDay;
use App\Models\Venue;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Crea la convención como evento con módulos, usando únicamente datos
 * verificados en pe.mmmoficial.org (ver config/convention.php). Es
 * idempotente: no sobrescribe lo editado desde el panel.
 */
class ConventionSeeder extends Seeder
{
    public function run(): void
    {
        $slug = Str::slug(config('convention.name').' '.substr(config('convention.starts_at'), 0, 4));

        $event = ChurchEvent::query()->where('slug', $slug)->first();

        if (! $event) {
            $event = new ChurchEvent([
                'title' => config('convention.name'),
                'organization' => config('convention.organization'),
                'theme' => config('convention.theme'),
                'city' => config('convention.city'),
                'starts_on' => config('convention.starts_at'),
                'ends_on' => config('convention.ends_at'),
                'location' => config('convention.venue'),
                'is_published' => true,
                'is_featured' => true,
                'has_program' => true,
                'has_venues' => true,
                'has_food' => true,
                'has_donations' => true,
                'has_accommodations' => true,
                'has_tourism' => true,
            ]);
            $event->slug = $slug;
            $event->save();
        }

        if (! $event->venues()->where('name', config('convention.venue'))->exists()) {
            $venue = new Venue(['name' => config('convention.venue'), 'kind' => VenueKind::Main, 'is_published' => true, 'sort_order' => 1]);
            $venue->church_event_id = $event->id;
            $venue->save();
        }

        foreach (CarbonPeriod::create($event->starts_on, $event->endDate()) as $date) {
            $day = Carbon::parse($date)->toDateString();

            if (! ProgramDay::query()->forEvent($event)->whereDate('date', $day)->exists()) {
                ProgramDay::query()->forceCreate(['church_event_id' => $event->id, 'date' => $day]);
            }
        }
    }
}
