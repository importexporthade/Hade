<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\StoreSetting;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /** How many frequent questions the home page shows before pointing to the full page. */
    private const int HOME_QUESTIONS = 6;

    /**
     * The home page: current banners, then the sections the owner arranged in the manager,
     * closing with the first frequent questions so doubts are answered before the chat.
     */
    public function __invoke(): View
    {
        $sections = HomeSection::query()
            ->where('is_active', true)
            ->with('category')
            ->orderBy('sort_order')
            ->get()
            ->reject(fn (HomeSection $section): bool => $section->category !== null && ! $section->category->is_visible);

        $questionsPage = Page::query()->published()->where('slug', 'preguntas-frecuentes')->first();

        return view('store.home', [
            'banners' => Banner::query()->showing()->with(['linkProduct.category', 'linkCategory', 'linkPage'])->orderBy('sort_order')->get(),
            'sections' => $sections,
            'categories' => Category::query()->visible()->orderBy('sort_order')->get(),
            'questionsPage' => $questionsPage,
            'questions' => array_slice($questionsPage?->questions() ?? [], 0, self::HOME_QUESTIONS),
            'whatsappUrl' => StoreSetting::current()->whatsappUrl(),
        ]);
    }
}
