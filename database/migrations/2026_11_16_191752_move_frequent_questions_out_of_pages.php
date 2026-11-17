<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const string PAGE_SLUG = 'preguntas-frecuentes';

    /**
     * Copies the questions the owner wrote in the "Preguntas frecuentes" page into their own
     * table, reading each heading as a question and what follows it as its answer, and then
     * deletes the page: the questions now have their own section and page.
     */
    public function up(): void
    {
        $page = DB::table('pages')->where('slug', self::PAGE_SLUG)->first();

        if ($page === null) {
            return;
        }

        $now = now();

        $rows = collect($this->questionsIn((string) $page->content))
            ->values()
            ->map(fn (array $question, int $position): array => [
                ...$question,
                'is_active' => true,
                'sort_order' => $position + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        if (! DB::table('frequent_questions')->exists()) {
            DB::table('frequent_questions')->insert($rows->all());
        }

        DB::table('pages')->where('id', $page->id)->delete();
    }

    /**
     * The deleted page is not recreated; the questions go with their table when it is rolled back.
     */
    public function down(): void
    {
        //
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function questionsIn(string $html): array
    {
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NODEFDTD);

        $questions = [];

        foreach ($document->getElementsByTagName('body')->item(0)?->childNodes ?? [] as $node) {
            if ($node instanceof DOMElement && strtolower($node->tagName) === 'h2') {
                $questions[] = ['question' => trim($node->textContent), 'answer' => ''];
            } elseif ($questions !== [] && trim($node->textContent) !== '') {
                $questions[array_key_last($questions)]['answer'] .= $document->saveHTML($node);
            }
        }

        return array_values(array_filter($questions, fn (array $question): bool => $question['question'] !== '' && $question['answer'] !== ''));
    }
};
