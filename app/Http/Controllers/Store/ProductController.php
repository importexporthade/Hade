<?php

namespace App\Http\Controllers\Store;

use App\Enums\ProductEventType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ProductMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** How many products "También te puede gustar" suggests under a product. */
    private const int SUGGESTIONS = 5;

    /**
     * Every visible product, the ones whose name matches the search box, the ones the
     * owner marked as new for the "Novedades" page, or the ones on sale for "Ofertas".
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $isLatest = $request->query('collection') === 'latest';
        $isOffers = $request->query('collection') === 'offers';

        $productsQuery = Product::query()
            ->visible()
            ->when($search !== '', fn ($query) => $query->whereLike('name', "%{$search}%"));

        if ($isLatest) {
            $productsQuery->markedAsNew();
        } elseif ($isOffers) {
            $productsQuery->onSale()->orderBy('sort_order');
        } else {
            $productsQuery->orderBy('sort_order');
        }

        $products = $productsQuery
            ->paginate(24)
            ->withQueryString();

        return view('store.products.index', [
            'products' => $products,
            'search' => $search,
            'isLatest' => $isLatest,
            'isOffers' => $isOffers,
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless(Product::query()->visible()->whereKey($product->id)->exists(), 404);

        ProductMetrics::record($request, $product, ProductEventType::View);

        $product->load('category');

        return view('store.products.show', [
            'product' => $product,
            'related' => $this->suggestionsFor($product),
        ]);
    }

    /**
     * Up to five products for "También te puede gustar", picked at random on each visit so
     * every product gets its turn: products in stock come first, and within them the ones of
     * the same category, filled with other categories when it has fewer. Products out of
     * stock only fill the places left over.
     *
     * @return Collection<int, Product>
     */
    private function suggestionsFor(Product $product): Collection
    {
        return Product::query()
            ->visible()
            ->whereKeyNot($product->id)
            ->orderByDesc('is_in_stock')
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [$product->category_id])
            ->inRandomOrder()
            ->limit(self::SUGGESTIONS)
            ->get();
    }
}
