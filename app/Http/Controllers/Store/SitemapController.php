<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Tells search engines which storefront pages exist, so every visible product and category
 * can be found even before anything links to it.
 */
class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $products = Product::query()->visible()->orderBy('sort_order')->get(['slug', 'updated_at']);

        /** @var list<array{loc: string, lastmod: ?Carbon}> $urls */
        $urls = [
            ['loc' => route('home'), 'lastmod' => $products->max('updated_at')],
            ['loc' => route('products.index'), 'lastmod' => $products->max('updated_at')],
            ...Category::query()->visible()->orderBy('sort_order')->get(['slug', 'updated_at'])
                ->map(fn (Category $category): array => ['loc' => route('categories.show', $category), 'lastmod' => $category->updated_at]),
            ...$products->map(fn (Product $product): array => ['loc' => route('products.show', $product), 'lastmod' => $product->updated_at]),
            ...Page::query()->published()->orderBy('sort_order')->get(['slug', 'updated_at'])
                ->map(fn (Page $page): array => ['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at]),
            ['loc' => route('complaints.create'), 'lastmod' => null],
        ];

        return response()
            ->view('store.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Keeps crawlers out of the manager and the metrics endpoints, and points them to the sitemap.
     */
    public function robots(): Response
    {
        $rules = implode("\n", [
            'User-agent: *',
            'Disallow: /manager',
            'Disallow: /metrics',
            '',
            'Sitemap: '.route('sitemap'),
        ]);

        return response($rules."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
