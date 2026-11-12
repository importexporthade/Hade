<?php

namespace Tests\Feature;

use App\Enums\PageVisibility;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use Database\Seeders\InformationPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformationPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_store_gets_its_legal_and_help_pages_with_its_contact_details(): void
    {
        StoreSetting::current()->update(['whatsapp_number' => '51 988940359', 'email' => 'import.export.hade@gmail.com']);

        $this->seed(InformationPagesSeeder::class);

        $this->get('/information/politica-de-privacidad')
            ->assertOk()
            ->assertSee('Ley N° 29733')
            ->assertSee('derechos ARCO')
            ->assertSee('51 988940359')
            ->assertSee('import.export.hade@gmail.com');
        $this->get('/information/terminos-y-condiciones')->assertOk()->assertSee('Uso de la página web');
        $this->get('/information/preguntas-frecuentes')->assertOk()->assertSee('¿Cómo hago un pedido?');
    }

    public function test_the_privacy_policy_explains_the_cookies_the_store_sets(): void
    {
        $this->seed(InformationPagesSeeder::class);

        $this->get('/information/politica-de-privacidad')
            ->assertOk()
            ->assertSee('hade-session')
            ->assertSee('XSRF-TOKEN')
            ->assertSee('hade_visitor');
    }

    public function test_the_returns_page_is_a_hidden_template_until_the_owner_publishes_it(): void
    {
        $this->seed(InformationPagesSeeder::class);

        $this->get('/information/cambios-y-devoluciones')->assertNotFound();
        $this->get('/products')->assertSee('Contenido próximamente');
        $this->assertStringContainsString('Plantilla para completar', Page::query()->where('slug', 'cambios-y-devoluciones')->value('content'));
    }

    public function test_legal_pages_are_linked_only_from_the_footer_and_not_the_information_menu(): void
    {
        $this->seed(InformationPagesSeeder::class);

        $html = $this->get('/products')->assertOk()->getContent();

        // Help pages appear in the desktop menu, the mobile menu and the footer; legal pages only in the footer
        $this->assertSame(3, substr_count($html, 'href="'.route('pages.show', 'preguntas-frecuentes').'"'));
        $this->assertSame(1, substr_count($html, 'href="'.route('pages.show', 'politica-de-privacidad').'"'));
        $this->assertSame(1, substr_count($html, 'href="'.route('pages.show', 'terminos-y-condiciones').'"'));
    }

    public function test_the_home_page_answers_the_first_frequent_questions_from_their_page(): void
    {
        StoreSetting::current()->update(['whatsapp_number' => '51 988940359']);
        $this->seed(InformationPagesSeeder::class);

        $response = $this->get('/')->assertOk();

        $response->assertSeeInOrder(['Resolvemos tus dudas', '¿Cómo hago un pedido?', '¿Cómo pago?', '¿Cómo los retiro sin dañar mis uñas?'])
            ->assertSee('Elige tus productos, agrégalos al carrito')
            ->assertDontSee('¿Necesito una lámpara?')
            ->assertSee('href="'.route('pages.show', 'preguntas-frecuentes').'"', false)
            ->assertSee('https://wa.me/51988940359', false)
            ->assertSee('"@type":"FAQPage"', false);
    }

    public function test_the_home_page_follows_the_questions_the_owner_edits(): void
    {
        Page::factory()->create([
            'slug' => 'preguntas-frecuentes',
            'content' => '<p>Intro.</p><h2>¿Venden al por mayor?</h2><p>Sí, desde 12 unidades.</p><script>alert(1)</script>',
        ]);

        $this->get('/')
            ->assertSee('¿Venden al por mayor?')
            ->assertSee('Sí, desde 12 unidades.')
            ->assertDontSee('Intro.')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_the_home_page_has_no_questions_section_without_a_published_questions_page(): void
    {
        Page::factory()->create(['slug' => 'preguntas-frecuentes', 'visibility' => PageVisibility::Hidden, 'content' => '<h2>¿Oculta?</h2><p>Sí.</p>']);

        $this->get('/')->assertOk()->assertDontSee('Resolvemos tus dudas')->assertDontSee('¿Oculta?');
    }

    public function test_the_footer_shows_who_sells(): void
    {
        StoreSetting::current()->update([
            'legal_name' => 'Hade Import Export S.A.C.',
            'ruc' => '20612345678',
            'legal_address' => 'Av. Larco 123, Miraflores, Lima',
        ]);

        $this->get('/products')
            ->assertSee('Hade Import Export S.A.C.')
            ->assertSee('RUC 20612345678')
            ->assertSee('Av. Larco 123, Miraflores, Lima');
    }

    public function test_the_footer_leaves_out_columns_with_nothing_to_show(): void
    {
        $this->get('/products')
            ->assertOk()
            ->assertSee('<h2 class="text-sm font-bold">Legal</h2>', false)
            ->assertDontSee('<h2 class="text-sm font-bold">Categorías</h2>', false)
            ->assertDontSee('<h2 class="text-sm font-bold">Información</h2>', false)
            ->assertDontSee('<h2 class="text-sm font-bold">Contáctanos</h2>', false);

        Product::factory()->for(Category::factory())->create();
        $this->seed(InformationPagesSeeder::class);
        StoreSetting::current()->update(['instagram_url' => 'https://www.instagram.com/hade.pe']);

        $this->get('/products')
            ->assertSee('<h2 class="text-sm font-bold">Categorías</h2>', false)
            ->assertSee('<h2 class="text-sm font-bold">Información</h2>', false)
            ->assertSee('<h2 class="text-sm font-bold">Contáctanos</h2>', false);
    }

    public function test_the_footer_links_the_legal_pages_once_they_exist(): void
    {
        $this->get('/products')->assertSee('Contenido próximamente');

        $this->seed(InformationPagesSeeder::class);

        $this->get('/products')
            ->assertSee(route('pages.show', 'politica-de-privacidad'), false)
            ->assertSee(route('pages.show', 'terminos-y-condiciones'), false)
            ->assertSee(route('pages.show', 'preguntas-frecuentes'), false)
            ->assertDontSee(route('pages.show', 'cambios-y-devoluciones'), false);
    }

    public function test_pages_the_owner_already_edited_are_left_alone(): void
    {
        Page::factory()->create(['slug' => 'politica-de-privacidad', 'title' => 'Privacidad', 'content' => '<p>Mi texto.</p>']);

        $this->seed(InformationPagesSeeder::class);
        $this->seed(InformationPagesSeeder::class);

        $this->assertSame('<p>Mi texto.</p>', Page::query()->where('slug', 'politica-de-privacidad')->value('content'));
        $this->assertSame(4, Page::query()->count());
    }
}
