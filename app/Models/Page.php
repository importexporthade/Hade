<?php

namespace App\Models;

use App\Enums\PageVisibility;
use Database\Factories\PageFactory;
use Dom\Element;
use Dom\HTMLDocument;
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
     * The page read as questions and answers: each heading is a question and everything
     * after it, up to the next heading, is its answer. The home page shows the frequent
     * questions this way, so the owner edits each one in a single place.
     *
     * @return list<array{question: string, answer: string}>
     */
    public function questions(): array
    {
        $document = HTMLDocument::createFromString('<!DOCTYPE html><body>'.str($this->content)->sanitizeHtml(), LIBXML_NOERROR);
        $questions = [];

        foreach ($document->body->childNodes as $node) {
            if ($node instanceof Element && $node->tagName === 'H2') {
                $questions[] = ['question' => trim($node->textContent), 'answer' => ''];
            } elseif ($questions !== [] && trim($node->textContent) !== '') {
                $questions[array_key_last($questions)]['answer'] .= $document->saveHtml($node);
            }
        }

        return array_values(array_filter($questions, fn (array $question): bool => $question['question'] !== '' && $question['answer'] !== ''));
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
