<?php

namespace App\Http\Controllers\Store;

use App\Enums\HomeSectionType;
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
            ->get();

        $productsBySection = $sections->mapWithKeys(
            fn (HomeSection $section): array => [$section->getKey() => $section->products()],
        );

        $renderedSections = $sections
            ->filter(fn (HomeSection $section): bool => $productsBySection->get($section->getKey())->isNotEmpty())
            ->values();

        // The first three sections are separated by fixed editorial blocks, so they start
        // green after the cream categories. "Tu estilo" separates the remaining sections;
        // that tail restarts by parity so its final section is always cream before the footer.
        $trailingAlternatingSectionCount = $renderedSections
            ->slice(3)
            ->reject(fn (HomeSection $section): bool => $section->type === HomeSectionType::Offers)
            ->count();

        $trailingSectionsStartDark = $trailingAlternatingSectionCount > 0
            && $trailingAlternatingSectionCount % 2 === 0;

        $darkBackgroundsBySection = [];
        $leadingAlternatingIndex = 0;
        $trailingAlternatingIndex = 0;

        foreach ($renderedSections as $position => $section) {
            if ($section->type === HomeSectionType::Offers) {
                continue;
            }

            if ($position < 3) {
                $darkBackgroundsBySection[$section->getKey()] = ++$leadingAlternatingIndex % 2 === 1;

                continue;
            }

            $trailingAlternatingIndex++;
            $darkBackgroundsBySection[$section->getKey()] = ($trailingAlternatingIndex + ($trailingSectionsStartDark ? 1 : 0)) % 2 === 0;
        }

        $questionsPage = Page::query()->published()->where('slug', 'preguntas-frecuentes')->first();

        return view('store.home', [
            'banners' => Banner::query()->showing()->with(['linkProduct.category', 'linkCategory', 'linkPage'])->orderBy('sort_order')->get(),
            'sections' => $sections,
            'productsBySection' => $productsBySection,
            'darkBackgroundsBySection' => $darkBackgroundsBySection,
            'categories' => Category::query()->visible()->orderBy('sort_order')->get(),
            'questionsPage' => $questionsPage,
            'questions' => array_slice($questionsPage?->questions() ?? [], 0, self::HOME_QUESTIONS),
            'whatsappUrl' => StoreSetting::current()->whatsappUrl(),
        ]);
    }
}
