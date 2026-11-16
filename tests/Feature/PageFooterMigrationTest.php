<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageFooterMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_pages_keep_their_content_and_effective_placement_after_the_migration(): void
    {
        $privacy = Page::factory()->create(['slug' => 'politica-de-privacidad', 'title' => 'Mi privacidad', 'visibility' => 'menu_and_footer']);
        $terms = Page::factory()->create(['slug' => 'terminos-y-condiciones', 'visibility' => 'menu']);
        $returns = Page::factory()->create(['slug' => 'cambios-y-devoluciones', 'visibility' => 'hidden']);
        $help = Page::factory()->create(['visibility' => 'menu_and_footer']);
        $menu = Page::factory()->create(['visibility' => 'menu']);
        $hidden = Page::factory()->create(['visibility' => 'hidden']);
        $columns = ['id', 'title', 'slug', 'content', 'sort_order', 'created_at', 'updated_at'];
        $originalContent = DB::table('pages')->orderBy('id')->get($columns)->map(fn (object $page): array => (array) $page)->all();
        $migration = require database_path('migrations/2026_11_16_182041_add_footer_section_to_pages_table.php');
        $migration->down();

        $migration->up();

        $this->assertSame($originalContent, DB::table('pages')->orderBy('id')->get($columns)->map(fn (object $page): array => (array) $page)->all());
        $this->assertDatabaseHas('pages', ['id' => $privacy->id, 'visibility' => 'footer', 'footer_section' => 'legal']);
        $this->assertDatabaseHas('pages', ['id' => $terms->id, 'visibility' => 'footer', 'footer_section' => 'legal']);
        $this->assertDatabaseHas('pages', ['id' => $returns->id, 'visibility' => 'hidden', 'footer_section' => 'legal']);
        $this->assertDatabaseHas('pages', ['id' => $help->id, 'visibility' => 'menu_and_footer', 'footer_section' => 'information']);
        $this->assertDatabaseHas('pages', ['id' => $menu->id, 'visibility' => 'menu', 'footer_section' => 'information']);
        $this->assertDatabaseHas('pages', ['id' => $hidden->id, 'visibility' => 'hidden', 'footer_section' => 'information']);
    }
}
