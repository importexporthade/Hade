<?php

namespace App\Models;

use App\Enums\BannerLinkType;
use App\Models\Concerns\DeletesStoredImages;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'title', 'subtitle', 'image', 'mobile_image', 'link_type', 'link_product_id', 'link_category_id',
    'link_page_id', 'link_url', 'button_label', 'is_active', 'starts_at', 'ends_at', 'sort_order',
])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use DeletesStoredImages, HasFactory;

    /**
     * Mirrors the column default so a new banner in memory already has a destination.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'link_type' => 'none',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'link_type' => BannerLinkType::class,
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Only the field of the chosen destination is kept, so switching from "a product" to
     * "a category" never leaves the old product behind.
     */
    protected static function booted(): void
    {
        static::saving(function (Banner $banner): void {
            $type = $banner->link_type ?? BannerLinkType::None;

            $banner->link_product_id = $type === BannerLinkType::Product ? $banner->link_product_id : null;
            $banner->link_category_id = $type === BannerLinkType::Category ? $banner->link_category_id : null;
            $banner->link_page_id = $type === BannerLinkType::Page ? $banner->link_page_id : null;
            $banner->link_url = $type === BannerLinkType::External ? $banner->link_url : null;
        });
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function linkProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'link_product_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function linkCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'link_category_id');
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function linkPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'link_page_id');
    }

    /**
     * The address the banner opens, or null when it leads nowhere — including when its
     * product, category or page was deleted or hidden, so a tap never lands on a missing page.
     */
    public function linkUrl(): ?string
    {
        return match ($this->link_type) {
            BannerLinkType::Product => $this->linkProduct?->isShownInStore()
                ? route('products.show', $this->linkProduct)
                : null,
            BannerLinkType::Category => $this->linkCategory?->isShownInStore() ? route('categories.show', $this->linkCategory) : null,
            BannerLinkType::AllProducts => route('products.index'),
            BannerLinkType::Page => $this->linkPage?->isPublished() ? route('pages.show', $this->linkPage) : null,
            BannerLinkType::WhatsApp => StoreSetting::current()->whatsappUrl(),
            BannerLinkType::External => $this->link_url ?: null,
            default => null,
        };
    }

    /**
     * Where the banner leads, in words, for the manager's list.
     */
    public function destinationLabel(): string
    {
        return match ($this->link_type) {
            BannerLinkType::Product => 'Producto: '.($this->linkProduct?->name ?? 'eliminado'),
            BannerLinkType::Category => 'Categoría: '.($this->linkCategory?->name ?? 'eliminada'),
            BannerLinkType::Page => 'Página: '.($this->linkPage?->title ?? 'eliminada'),
            BannerLinkType::External => (string) parse_url((string) $this->link_url, PHP_URL_HOST),
            default => $this->link_type?->getLabel() ?? BannerLinkType::None->getLabel(),
        };
    }

    /**
     * Banners switched on whose optional date window includes now.
     *
     * @param  Builder<Banner>  $query
     */
    #[Scope]
    protected function showing(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /**
     * @return list<string>
     */
    protected function storedImageAttributes(): array
    {
        return ['image', 'mobile_image'];
    }
}
