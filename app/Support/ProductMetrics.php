<?php

namespace App\Support;

use App\Enums\ProductEventType;
use App\Http\Middleware\IdentifyVisitor;
use App\Models\Product;
use App\Models\ProductEvent;
use Illuminate\Http\Request;

/**
 * Records what customers do with products. Robots (search engines, and the link previews
 * WhatsApp, Facebook or TikTok make when someone shares a product) and the owner browsing
 * her own store while signed in are left out, so the figures reflect real customers.
 */
class ProductMetrics
{
    private const string ROBOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|curl|wget|headless|lighthouse/i';

    public static function shouldRecord(Request $request): bool
    {
        if ($request->user()?->is_admin) {
            return false;
        }

        $userAgent = (string) $request->userAgent();

        return $userAgent !== '' && ! preg_match(self::ROBOT_PATTERN, $userAgent);
    }

    public static function visitorId(Request $request): ?string
    {
        return $request->attributes->get(IdentifyVisitor::ATTRIBUTE);
    }

    public static function record(Request $request, Product $product, ProductEventType $type, int $quantity = 1): void
    {
        if (! self::shouldRecord($request)) {
            return;
        }

        ProductEvent::query()->create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'unit_price' => $product->price,
            'visitor_id' => self::visitorId($request),
        ]);
    }
}
