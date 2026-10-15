<?php

namespace App\Http\Controllers;

use App\Enums\VenueKind;
use App\Models\Accommodation;
use App\Models\Banner;
use App\Models\DishOffering;
use App\Models\DonationMethod;
use App\Models\EventSetting;
use App\Models\Faq;
use App\Models\ProgramDay;
use App\Models\TouristPlace;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\View\View;

/**
 * Páginas públicas. Solo muestran contenido publicado desde el panel.
 */
class PageController extends Controller
{
    public function home(): View
    {
        $nextDay = ProgramDay::query()
            ->whereDate('date', '>=', today())
            ->whereHas('items', fn ($q) => $q->published())
            ->with(['items' => fn (HasMany $q) => $q->published()->limit(5)])
            ->orderBy('date')
            ->first();

        return view('pages.home', [
            'banners' => Banner::query()->published()->ordered()->get(),
            'mainVenue' => Venue::query()->published()->where('kind', VenueKind::Main)->ordered()->first(),
            'nextDay' => $nextDay,
            'featuredOfferings' => EventSetting::current()->reservations_open
                ? DishOffering::query()->bookable()->with('dish')->orderBy('serve_date')->orderBy('slot_start')->get()->unique('dish_id')->take(3)
                : collect(),
            'places' => TouristPlace::query()->published()->ordered()->limit(3)->get(),
            'faqs' => Faq::query()->published()->ordered()->limit(4)->get(),
        ]);
    }

    public function program(): View
    {
        return view('pages.program', [
            'days' => ProgramDay::query()
                ->with(['items' => fn (HasMany $query) => $query->published()->with('venue:id,name')])
                ->orderBy('date')
                ->get(),
        ]);
    }

    public function venue(): View
    {
        return view('pages.venue', [
            'venues' => Venue::query()->published()->ordered()->get(),
        ]);
    }

    public function donations(): View
    {
        return view('pages.donations', [
            'methods' => DonationMethod::query()->published()->ordered()->get(),
        ]);
    }

    public function accommodations(): View
    {
        return view('pages.accommodations', [
            'accommodations' => Accommodation::query()->published()->ordered()->get(),
        ]);
    }

    public function tourism(): View
    {
        return view('pages.tourism', [
            'places' => TouristPlace::query()->published()->ordered()->get(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'faqs' => Faq::query()->published()->ordered()->get(),
        ]);
    }
}
