<?php

namespace App\Models;

use Database\Factories\FrequentQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['question', 'answer', 'is_active', 'sort_order'])]
class FrequentQuestion extends Model
{
    /** @use HasFactory<FrequentQuestionFactory> */
    use HasFactory;

    /** How many questions the home page shows before pointing to the full page. */
    public const int HOME_LIMIT = 6;

    /**
     * Mirrors the column default so a new question in memory is already active.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The active questions customers see, in the owner's order, or none while the owner
     * has the whole section turned off.
     *
     * @return Collection<int, self>
     */
    public static function shownInStore(?int $limit = null): Collection
    {
        if (! StoreSetting::current()->shows_frequent_questions) {
            return new Collection;
        }

        return self::query()->active()->orderBy('sort_order')->orderBy('id')->limit($limit)->get();
    }

    /**
     * Whether the store shows the section at all: it is turned on and has an active question.
     */
    public static function sectionIsShown(): bool
    {
        return StoreSetting::current()->shows_frequent_questions && self::query()->active()->exists();
    }

    /**
     * The answer as it is safe to print in the store.
     */
    public function safeAnswer(): string
    {
        return str($this->answer)->sanitizeHtml()->toString();
    }

    /**
     * The answer as plain words, for search engines.
     */
    public function plainAnswer(): string
    {
        return str(strip_tags(str_replace('<', ' <', $this->safeAnswer())))->squish()->toString();
    }

    /**
     * @param  Builder<FrequentQuestion>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
