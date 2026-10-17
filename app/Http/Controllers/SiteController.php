<?php

namespace App\Http\Controllers;

use App\Models\AboutBlock;
use App\Models\Banner;
use App\Models\ChurchEvent;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\Leader;
use App\Models\MediaOutlet;
use App\Models\Motto;
use App\Models\SiteSetting;
use App\Models\Teaching;
use App\Models\Verse;
use App\Support\YouTube;
use Illuminate\View\View;

/**
 * Páginas institucionales del MMM Huaraz. Solo muestran contenido publicado.
 */
class SiteController extends Controller
{
    public function home(): View
    {
        $featured = ChurchEvent::featured();
        $banners = Banner::query()->published()->ordered()->get();
        $teachings = Teaching::query()->published()->latestFirst()->limit(6)->get();

        // Collage de «Nuestra familia»: las fotos elegidas en Panel › Fotos; si aún
        // no hay ninguna, otras imágenes ya subidas (eventos, enseñanzas, banners).
        $gallery = GalleryPhoto::query()->forHome()->get()
            ->map(fn (GalleryPhoto $photo) => ['url' => $photo->imageUrl(), 'alt' => $photo->image_alt]);

        $gallery = $gallery->isNotEmpty() ? $gallery : collect()
            ->merge(ChurchEvent::query()->published()->whereNotNull('image_path')->latest('starts_on')->limit(4)->get())
            ->merge($teachings->filter->imageUrl())
            ->merge($banners->filter->imageUrl())
            ->map(fn ($model) => ['url' => $model->imageUrl(), 'alt' => $model->image_alt ?? ''])
            ->unique('url')
            ->take(4)
            ->values();

        return view('pages.home', [
            'banners' => $banners,
            'featured' => $featured,
            'teachings' => $teachings,
            'gallery' => $gallery,
            'motto' => Motto::current(),
            'media' => MediaOutlet::query()->published()->ordered()->get(),
            'verse' => Verse::query()->published()->ordered()->first(),
            'nextEvent' => ChurchEvent::query()->published()->upcoming()->when($featured, fn ($q) => $q->whereKeyNot($featured->id))->first(),
        ]);
    }

    public function about(): View
    {
        return view('pages.about', [
            'blocks' => AboutBlock::query()->published()->ordered()->get(),
            'leaders' => Leader::query()->published()->ordered()->get(),
        ]);
    }

    public function bethel(): View
    {
        $channelId = SiteSetting::current()->bethel_channel_id ?: config('bible.bethel_channel_id');

        return view('pages.bethel', [
            'liveUrl' => YouTube::liveEmbedUrl($channelId),
            'channelUrl' => 'https://www.youtube.com/channel/'.$channelId,
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'faqs' => Faq::query()->published()->ordered()->get(),
        ]);
    }
}
