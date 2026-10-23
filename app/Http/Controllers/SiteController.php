<?php

namespace App\Http\Controllers;

use App\Enums\TeachingCategory;
use App\Models\AboutBlock;
use App\Models\Banner;
use App\Models\ChurchEvent;
use App\Models\DoctrineBook;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\Leader;
use App\Models\MediaOutlet;
use App\Models\Motto;
use App\Models\SiteSetting;
use App\Models\Teaching;
use App\Models\Verse;
use App\Services\GameCatalog;
use App\Support\FlipBook;
use App\Support\YouTube;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Páginas institucionales del MMM Huaraz. Solo muestran contenido publicado.
 */
class SiteController extends Controller
{
    public function home(GameCatalog $games): View
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
            'games' => $games->catalog()->filter(fn ($game) => $game['ready']),
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

    /**
     * Doctrina: el Doctrinario en el lector propio (si ya se subió el PDF) o, si
     * no, el libro de FlipHTML5; y las enseñanzas de esa categoría.
     */
    public function doctrine(): View
    {
        $book = DoctrineBook::current();
        // Enlaces firmados de 3 horas: no sirven fuera de este sitio ni después.
        $expires = now()->addHours(3);

        return view('pages.doctrine', [
            'book' => $book,
            'toc' => $book?->index() ?? [],
            'pages' => $book ? collect(range(1, $book->page_count))->map(fn (int $n) => URL::temporarySignedRoute('doctrine.page', $expires, [$book, $n]))->all() : [],
            'bookUrl' => $book ? null : (FlipBook::url(SiteSetting::current()->doctrine_book_url) ?? FlipBook::url(config('bible.doctrine_book_url'))),
            'teachings' => Teaching::query()->published()->where('category', TeachingCategory::Doctrine)->latestFirst()->limit(6)->get(),
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'faqs' => Faq::query()->published()->ordered()->get(),
        ]);
    }
}
