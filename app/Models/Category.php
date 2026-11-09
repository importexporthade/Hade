<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredImages;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'slug', 'description', 'image', 'is_visible', 'sort_order'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use DeletesStoredImages, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Categories the store shows: switched on in the manager and with at least one visible
     * product. One that is empty, or whose products are all hidden, hides itself and comes
     * back as soon as one of them is shown again.
     *
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true)
            ->whereHas('products', fn (Builder $query) => $query->where('is_visible', true));
    }

    /**
     * @param  Builder<Category>  $query
     */
    #[Scope]
    protected function withVisibleProductsFlag(Builder $query): void
    {
        $query->withExists(['products as has_visible_products' => fn (Builder $query) => $query->where('is_visible', true)]);
    }

    public function hasVisibleProducts(): bool
    {
        return (bool) ($this->has_visible_products ?? $this->products()->where('is_visible', true)->exists());
    }

    /**
     * Whether the store shows this category, by the same rule as the visible scope.
     */
    public function isShownInStore(): bool
    {
        return $this->is_visible && $this->hasVisibleProducts();
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    /**
     * @return list<string>
     */
    protected function storedImageAttributes(): array
    {
        return ['image'];
    }
}
