<?php

namespace App\Models;

use App\Enums\ProductEventType;
use Database\Factories\ProductEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing a customer did with a product, recorded for metrics. Rows are only added,
 * never changed, so there is no updated_at.
 */
#[Fillable(['product_id', 'checkout_id', 'type', 'quantity', 'unit_price', 'visitor_id'])]
class ProductEvent extends Model
{
    /** @use HasFactory<ProductEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductEventType::class,
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Checkout, $this>
     */
    public function checkout(): BelongsTo
    {
        return $this->belongsTo(Checkout::class);
    }

    /**
     * @param  Builder<ProductEvent>  $query
     */
    #[Scope]
    protected function ofType(Builder $query, ProductEventType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * @param  Builder<ProductEvent>  $query
     */
    #[Scope]
    protected function inLastDays(Builder $query, int $days): void
    {
        $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
    }
}
