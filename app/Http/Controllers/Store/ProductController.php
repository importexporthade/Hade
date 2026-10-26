<?php

namespace App\Http\Controllers\Store;

use App\Enums\ProductEventType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ProductMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Every visible product, or the ones whose name matches the search box.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $isLatest = $request->query('collection') === 'latest';

        $productsQuery = Product::query()
            ->visible()
            ->when($search !== '', fn ($query) => $query->whereLike('name', "%{$search}%"));

        if ($isLatest) {
            $productsQuery->latest()->latest('id');
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
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless(Product::query()->visible()->whereKey($product->id)->exists(), 404);

        ProductMetrics::record($request, $product, ProductEventType::View);

        $product->load('category');

        $related = Product::query()
            ->visible()
            ->whereKeyNot($product->id)
            ->where('category_id', $product->category_id)
            ->orderBy('sort_order')
            ->limit(5)
            ->get();

        return view('store.products.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
