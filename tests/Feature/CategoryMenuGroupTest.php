<?php

namespace Tests\Feature;

use App\Enums\CategoryMenuGroup;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryMenuGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_store_menu_lists_each_category_under_the_group_the_owner_picked(): void
    {
        $this->withoutVite();
        $nails = Category::factory()->create(['name' => 'Stickers de uñas', 'menu_group' => CategoryMenuGroup::Nails]);
        $complements = Category::factory()->create(['name' => 'Limas', 'menu_group' => CategoryMenuGroup::Complements]);
        $discover = Category::factory()->create(['name' => 'Manicure rusa', 'menu_group' => CategoryMenuGroup::Discover]);
        Product::factory()->for($nails)->create();
        Product::factory()->for($complements)->create();
        Product::factory()->for($discover)->create();

        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$this->get('/products')->assertOk()->getContent());
        $xpath = new DOMXPath($document);

        foreach ([[$nails, 'Para tus uñas'], [$complements, 'Complementos'], [$discover, 'Para descubrir']] as [$category, $group]) {
            $link = 'a[@href="'.route('categories.show', $category).'"]';

            $this->assertSame(1, $xpath->query('//div[@id="menu-tienda"]//section[h3[normalize-space()="'.$group.'"]]//'.$link)->length, $category->name);
            $this->assertSame(1, $xpath->query('//div[@id="menu-tienda"]//'.$link)->length, $category->name);
        }
    }

    public function test_the_owner_picks_the_menu_group_when_creating_a_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageCategories::class)
            ->callAction('create', ['name' => 'Esmaltes', 'slug' => 'esmaltes', 'menu_group' => CategoryMenuGroup::Nails->value])
            ->assertHasNoActionErrors();

        $this->assertSame(CategoryMenuGroup::Nails, Category::query()->where('slug', 'esmaltes')->sole()->menu_group);
    }

    public function test_existing_categories_keep_the_group_their_name_gave_them(): void
    {
        Schema::table('categories', fn ($table) => $table->dropColumn('menu_group'));
        DB::table('categories')->insert([
            ['name' => 'Stickers Pedicure', 'slug' => 'stickers-pedicure'],
            ['name' => 'Lámparas', 'slug' => 'lamparas'],
            ['name' => 'Kits y Promociones', 'slug' => 'kits'],
        ]);

        (require database_path('migrations/2026_11_17_185354_add_menu_group_to_categories_table.php'))->up();

        $this->assertSame(
            ['stickers-pedicure' => 'nails', 'lamparas' => 'complements', 'kits' => 'discover'],
            DB::table('categories')->pluck('menu_group', 'slug')->all(),
        );
    }
}
