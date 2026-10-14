<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredImages;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'description', 'price', 'compare_at_price',
    'images', 'is_visible', 'is_featured', 'is_in_stock', 'sort_order',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use DeletesStoredImages, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'images' => 'array',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
            'is_in_stock' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && (float) $this->compare_at_price > (float) $this->price;
    }

    /**
     * Whole-number discount shown on the "-15 %" badge, or null when the product is not on sale.
     */
    public function discountPercentage(): ?int
    {
        if (! $this->isOnSale()) {
            return null;
        }

        return (int) round((1 - (float) $this->price / (float) $this->compare_at_price) * 100);
    }

    /**
     * Puts the product on sale: the regular price stays crossed out and the new price is
     * that much lower. Applying a new discount to a product already on sale starts from
     * its regular price, not from the current sale price.
     */
    public function applyDiscount(int $percentage): void
    {
        $regularPrice = (float) ($this->compare_at_price ?? $this->price);

        $this->update([
            'compare_at_price' => $regularPrice,
            'price' => round($regularPrice * (100 - $percentage) / 100, 2),
        ]);
    }

    /**
     * Ends the sale and restores the regular price.
     */
    public function removeDiscount(): void
    {
        if ($this->compare_at_price === null) {
            return;
        }

        $this->update([
            'price' => $this->compare_at_price,
            'compare_at_price' => null,
        ]);
    }

    public function coverImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * @return list<string>
     */
    protected function storedImageAttributes(): array
    {
        return ['images'];
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function onSale(Builder $query): void
    {
        $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
    }
}
