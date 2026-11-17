<?php

namespace Tests\Feature;

use App\Filament\Resources\FrequentQuestions\Pages\ManageFrequentQuestions;
use App\Models\FrequentQuestion;
use App\Models\Page;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class FrequentQuestionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_home_page_shows_the_first_six_active_questions_in_the_owner_order_and_links_to_all(): void
    {
        foreach (range(1, 8) as $position) {
            FrequentQuestion::factory()->create(['question' => "Pregunta {$position}", 'sort_order' => $position]);
        }
        FrequentQuestion::factory()->inactive()->create(['question' => 'Pregunta apagada', 'sort_order' => 0]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Resolvemos tus dudas', 'Pregunta 1', 'Pregunta 2', 'Pregunta 6'])
            ->assertDontSee('Pregunta 7')
            ->assertDontSee('Pregunta apagada')
            ->assertSee('href="'.route('frequent-questions').'"', false);
    }

    public function test_the_questions_page_lists_every_active_question_with_its_safe_answer(): void
    {
        foreach (range(1, 8) as $position) {
            FrequentQuestion::factory()->create(['question' => "Pregunta {$position}", 'sort_order' => $position]);
        }
        FrequentQuestion::factory()->create([
            'question' => '¿Venden al por mayor?',
            'answer' => '<p>Sí, desde 12 unidades.</p><script>alert(1)</script>',
            'sort_order' => 9,
        ]);
        FrequentQuestion::factory()->inactive()->create(['question' => 'Pregunta apagada']);

        $this->get(route('frequent-questions'))
            ->assertOk()
            ->assertSeeInOrder(['Pregunta 1', 'Pregunta 8', '¿Venden al por mayor?'])
            ->assertSee('Sí, desde 12 unidades.')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Pregunta apagada')
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_without_active_questions_the_section_and_its_page_disappear(): void
    {
        FrequentQuestion::factory()->inactive()->create(['question' => 'Pregunta apagada']);

        $this->get('/')->assertOk()->assertDontSee('Resolvemos tus dudas')->assertDontSee(route('frequent-questions'), false);
        $this->get(route('frequent-questions'))->assertNotFound();
    }

    public function test_turning_the_section_off_hides_it_from_the_home_page_the_menu_and_its_page(): void
    {
        FrequentQuestion::factory()->create(['question' => 'Pregunta visible']);
        StoreSetting::current()->update(['shows_frequent_questions' => false]);

        $this->get('/')->assertOk()->assertDontSee('Pregunta visible')->assertDontSee(route('frequent-questions'), false);
        $this->get(route('frequent-questions'))->assertNotFound();
    }

    public function test_the_menu_and_footer_link_to_the_questions_page(): void
    {
        FrequentQuestion::factory()->create();

        $html = $this->get('/products')->assertOk()->getContent();

        // The desktop menu, the mobile menu and the footer
        $this->assertSame(3, substr_count($html, 'href="'.route('frequent-questions').'"'));
    }

    public function test_the_old_questions_address_redirects_to_the_new_page(): void
    {
        $this->get('/information/preguntas-frecuentes')->assertMovedPermanently()->assertRedirect('/preguntas-frecuentes');
    }

    public function test_the_owner_adds_hides_and_deletes_questions(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageFrequentQuestions::class)
            ->callAction('create', ['question' => '¿Hacen envíos a provincia?', 'answer' => '<p>Sí, por agencia.</p>'])
            ->assertHasNoActionErrors();

        $question = FrequentQuestion::query()->sole();
        $this->assertTrue($question->is_active);
        $this->assertStringContainsString('Sí, por agencia.', $question->answer);

        Livewire::test(ManageFrequentQuestions::class)
            ->call('updateTableColumnState', 'is_active', (string) $question->id, false);
        $this->assertFalse($question->refresh()->is_active);

        $question->delete();
        $this->assertDatabaseEmpty('frequent_questions');
    }

    public function test_a_question_needs_its_text_and_its_answer(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageFrequentQuestions::class)
            ->callAction('create', ['question' => '', 'answer' => null])
            ->assertHasActionErrors(['question' => 'required', 'answer']);
    }

    public function test_the_owner_turns_the_whole_section_off_and_on(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageFrequentQuestions::class)->callAction('toggleSection');
        $this->assertFalse(StoreSetting::current()->shows_frequent_questions);

        Livewire::test(ManageFrequentQuestions::class)->callAction('toggleSection');
        $this->assertTrue(StoreSetting::current()->shows_frequent_questions);
    }

    public function test_the_questions_written_in_the_old_page_move_to_their_own_table_and_the_page_is_deleted(): void
    {
        $page = Page::factory()->create([
            'slug' => 'preguntas-frecuentes',
            'content' => '<p>Intro.</p><h2>¿Cómo pago?</h2><p>Con Yape o <strong>transferencia</strong>.</p><h2>¿Envían a Perú?</h2><ol><li>Sí, a todo el país.</li></ol><h2>Sin respuesta</h2>',
        ]);

        (require database_path('migrations/2026_11_16_191752_move_frequent_questions_out_of_pages.php'))->up();

        $this->assertSame(
            [
                ['question' => '¿Cómo pago?', 'answer' => '<p>Con Yape o <strong>transferencia</strong>.</p>', 'sort_order' => 1],
                ['question' => '¿Envían a Perú?', 'answer' => '<ol><li>Sí, a todo el país.</li></ol>', 'sort_order' => 2],
            ],
            DB::table('frequent_questions')->orderBy('sort_order')->get(['question', 'answer', 'sort_order'])->map(fn (object $row): array => (array) $row)->all(),
        );
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }
}
