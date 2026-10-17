<?php

namespace App\Models;

use App\Enums\PageVisibility;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'slug', 'content', 'visibility', 'sort_order'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    /**
     * Mirrors the column default so a new page in memory already has a place.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'visibility' => 'menu_and_footer',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => PageVisibility::class,
        ];
    }

    public function isPublished(): bool
    {
        return $this->visibility->isPublished();
    }

    public function isInFooter(): bool
    {
        return $this->visibility === PageVisibility::MenuAndFooter;
    }

    /**
     * Pages the store shows in its "Información" menu.
     *
     * @param  Builder<Page>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('visibility', '!=', PageVisibility::Hidden->value);
    }
}
