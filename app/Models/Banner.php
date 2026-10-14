<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredImages;
use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title', 'subtitle', 'image', 'mobile_image', 'link_url', 'button_label',
    'is_active', 'starts_at', 'ends_at', 'sort_order',
])]
class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use DeletesStoredImages, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
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
