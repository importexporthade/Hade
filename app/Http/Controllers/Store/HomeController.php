<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * The home page: current banners, then the sections the owner arranged in the manager.
     */
    public function __invoke(): View
    {
        $sections = HomeSection::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('sort_order')
            ->get()
            ->reject(fn (HomeSection $section): bool => $section->category !== null && ! $section->category->is_visible);

        return view('store.home', [
            'banners' => Banner::query()->showing()->orderBy('sort_order')->get(),
            'sections' => $sections,
            'categories' => Category::query()->visible()->orderBy('sort_order')->get(),
        ]);
    }
}
