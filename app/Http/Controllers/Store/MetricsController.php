<?php

namespace App\Http\Controllers\Store;

use App\Enums\ProductEventType;
use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Product;
use App\Support\ProductMetrics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Receives what happens inside the browser cart, which the server would not see otherwise:
 * products added or removed, and the order sent to WhatsApp.
 */
class MetricsController extends Controller
{
    public function cart(Request $request): Response
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'change' => ['required', Rule::in(['add', 'remove'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()->visible()->find($data['product_id']);

        if ($product !== null) {
            ProductMetrics::record(
                $request,
                $product,
                $data['change'] === 'add' ? ProductEventType::AddToCart : ProductEventType::RemoveFromCart,
                $data['quantity'],
            );
        }

        return response()->noContent();
    }

    public function checkout(Request $request): Response
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        if (! ProductMetrics::shouldRecord($request)) {
            return response()->noContent();
        }

        $products = Product::query()->visible()->findMany(collect($data['items'])->pluck('id'))->keyBy('id');

        $lines = collect($data['items'])
            ->filter(fn (array $item): bool => $products->has($item['id']))
            ->map(fn (array $item): array => ['product' => $products[$item['id']], 'quantity' => (int) $item['quantity']])
            ->values();

        if ($lines->isNotEmpty()) {
            Checkout::record($lines, ProductMetrics::visitorId($request));
        }

        return response()->noContent();
    }
}
