<?php

namespace App\Models;

use App\Enums\HomeSectionType;
use Database\Factories\HomeSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'subtitle', 'type', 'category_id', 'products_limit', 'is_active', 'sort_order'])]
class HomeSection extends Model
{
    /** @use HasFactory<HomeSectionFactory> */
    use HasFactory;

    /** The most products a home section shows: one row of five cards on a computer. */
    public const int MAX_PRODUCTS = 5;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => HomeSectionType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The visible products this section shows on the home page. A category section stays
     * empty, and so off the page, while the store hides its category, even though the
     * category's "Novedad" products still show in Novedades. Every section respects the
     * storefront's maximum number of product cards.
     *
     * @return Collection<int, Product>
     */
    public function products(): Collection
    {
        if ($this->type === HomeSectionType::Category && ! $this->category?->isShownInStore()) {
            return new Collection;
        }

        $query = Product::query()->visible();

        match ($this->type) {
            HomeSectionType::Offers => $query->onSale()->orderBy('sort_order'),
            HomeSectionType::Latest => $query->markedAsNew(),
            HomeSectionType::Category => $query->where('category_id', $this->category_id)->orderBy('sort_order'),
        };

        return $query->limit(min($this->products_limit, self::MAX_PRODUCTS))->get();
    }
}
