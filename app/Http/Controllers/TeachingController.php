<?php

namespace App\Http\Controllers;

use App\Enums\TeachingCategory;
use App\Models\Teaching;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeachingController extends Controller
{
    public function index(Request $request): View
    {
        $category = TeachingCategory::tryFrom((string) $request->query('categoria'));

        return view('teachings.index', [
            'teachings' => Teaching::query()
                ->published()
                ->when($category, fn ($q) => $q->where('category', $category))
                ->latestFirst()
                ->paginate(9)
                ->withQueryString(),
            'categories' => TeachingCategory::cases(),
            'current' => $category,
        ]);
    }

    public function show(string $slug): View
    {
        $teaching = Teaching::query()->published()->where('slug', $slug)->firstOrFail();

        return view('teachings.show', [
            'teaching' => $teaching,
            'related' => Teaching::query()->published()->whereKeyNot($teaching->id)->where('category', $teaching->category)->latestFirst()->limit(3)->get(),
        ]);
    }
}
