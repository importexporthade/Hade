<?php

namespace Tests\Feature;

use App\Models\Page;
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
        $this->get('/information/terminos-y-condiciones')->assertOk()->assertSee('incluyen IGV');
        $this->get('/information/cambios-y-devoluciones')->assertOk()->assertSee('7 días calendario');
        $this->get('/information/preguntas-frecuentes')->assertOk()->assertSee('¿Cómo hago un pedido?');
    }

    public function test_the_footer_links_the_legal_pages_once_they_exist(): void
    {
        $this->get('/products')->assertSee('Contenido próximamente');

        $this->seed(InformationPagesSeeder::class);

        $this->get('/products')
            ->assertDontSee('Contenido próximamente')
            ->assertSee(route('pages.show', 'politica-de-privacidad'), false)
            ->assertSee(route('pages.show', 'terminos-y-condiciones'), false)
            ->assertSee(route('pages.show', 'cambios-y-devoluciones'), false)
            ->assertSee(route('pages.show', 'preguntas-frecuentes'), false);
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
