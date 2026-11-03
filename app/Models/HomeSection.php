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
     * The visible products this section shows on the home page.
     *
     * @return Collection<int, Product>
     */
    public function products(?int $limit = null): Collection
    {
        $query = Product::query()->visible();

        match ($this->type) {
            HomeSectionType::Offers => $query->onSale()->orderBy('sort_order'),
            HomeSectionType::Latest => $query->markedAsNew(),
            HomeSectionType::Category => $query->where('category_id', $this->category_id)->orderBy('sort_order'),
        };

        return $query->limit($limit ?? $this->products_limit)->get();
    }
}
