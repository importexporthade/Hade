<?php

namespace App\Models;

use App\Enums\ProductEventType;
use Database\Factories\CheckoutFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * An order a customer sent to the store's WhatsApp from the cart.
 */
#[Fillable(['visitor_id', 'items_count', 'total'])]
class Checkout extends Model
{
    /** @use HasFactory<CheckoutFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<ProductEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ProductEvent::class);
    }

    /**
     * Records an order with one event per product, priced from the catalog rather than from
     * what the browser sent, so a tampered cart cannot skew the sales figures.
     *
     * @param  Collection<int, array{product: Product, quantity: int}>  $lines
     */
    public static function record(Collection $lines, ?string $visitorId): self
    {
        return DB::transaction(function () use ($lines, $visitorId): self {
            $checkout = self::query()->create([
                'visitor_id' => $visitorId,
                'items_count' => $lines->sum('quantity'),
                'total' => $lines->sum(fn (array $line): float => (float) $line['product']->price * $line['quantity']),
            ]);

            foreach ($lines as $line) {
                $checkout->events()->create([
                    'product_id' => $line['product']->id,
                    'type' => ProductEventType::Checkout,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['product']->price,
                    'visitor_id' => $visitorId,
                ]);
            }

            return $checkout;
        });
    }
}
