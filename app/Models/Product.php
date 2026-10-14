<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredImages;
use App\Support\ImageOptimizer;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /**
     * How much the customer saves, for the "¡Ahorra S/ 13.10!" note, or null when not on sale.
     */
    public function savings(): ?float
    {
        return $this->isOnSale() ? round((float) $this->compare_at_price - (float) $this->price, 2) : null;
    }

    /**
     * Public URLs of the photos in the order the owner set, or the placeholder when there are none.
     *
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return $this->photoUrls(thumbnails: false);
    }

    /**
     * Same photos in their small version, for tiles and gallery thumbnails; photos stored
     * before the optimizer existed have no thumbnail and fall back to the full photo.
     *
     * @return list<string>
     */
    public function thumbnailUrls(): array
    {
        return $this->photoUrls(thumbnails: true);
    }

    public function coverImageUrl(): string
    {
        return $this->imageUrls()[0];
    }

    public function coverThumbnailUrl(): string
    {
        return $this->thumbnailUrls()[0];
    }

    /**
     * @return list<string>
     */
    private function photoUrls(bool $thumbnails): array
    {
        $urls = collect($this->images ?? [])
            ->filter()
            ->map(fn (string $path): string => Storage::disk('public')->url(
                $thumbnails && ImageOptimizer::isOptimized($path) ? ImageOptimizer::thumbnailPath($path) : $path,
            ))
            ->values()
            ->all();

        return $urls !== [] ? $urls : [asset('images/product-placeholder.svg')];
    }

    /**
     * @return list<string>
     */
    protected function storedImageAttributes(): array
    {
        return ['images'];
    }

    /**
     * Products the store shows: switched on, and not inside a category the owner hid.
     *
     * @param  Builder<Product>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('category_id')
                ->orWhereHas('category', fn (Builder $query) => $query->where('is_visible', true)));
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
