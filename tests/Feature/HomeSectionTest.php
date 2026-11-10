<?php

namespace Tests\Feature;

use App\Enums\HomeSectionType;
use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Text;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HomeSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_offers_section_shows_visible_products_on_sale(): void
    {
        $onSale = Product::factory()->onSale()->create();
        Product::factory()->create();
        Product::factory()->onSale()->hidden()->create();

        $section = HomeSection::factory()->ofType(HomeSectionType::Offers)->create();

        $this->assertEquals([$onSale->id], $section->products()->modelKeys());
    }

    public function test_a_section_always_shows_up_to_five_products(): void
    {
        Product::factory()->onSale()->count(7)->create();

        // An older section may still have another number saved from when the owner chose it
        $this->assertCount(5, HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['products_limit' => 3])->products());
    }

    public function test_a_novedades_section_shows_the_five_newest_products_marked_as_new(): void
    {
        $markedAsNew = collect(range(6, 1))->map(fn (int $daysAgo): Product => Product::factory()->markedAsNew()->create(['created_at' => now()->subDays($daysAgo)]));
        Product::factory()->create(['is_featured' => false, 'created_at' => now()]);

        $section = HomeSection::factory()->ofType(HomeSectionType::Latest)->create();

        $this->assertEquals($markedAsNew->reverse()->take(5)->pluck('id')->all(), $section->products()->modelKeys());
    }

    public function test_a_featured_section_becomes_the_novedades_section_when_there_is_none(): void
    {
        DB::table('home_sections')->insert([
            ['title' => 'Destacados', 'type' => 'featured', 'products_limit' => 8, 'is_active' => true, 'sort_order' => 1],
            ['title' => 'Más destacados', 'type' => 'featured', 'products_limit' => 8, 'is_active' => true, 'sort_order' => 2],
        ]);

        $this->runFeaturedToNovedadesMigration();

        $this->assertSame(['Destacados'], HomeSection::query()->pluck('title')->all());
        $this->assertSame(HomeSectionType::Latest, HomeSection::query()->sole()->type);
    }

    public function test_a_featured_section_is_removed_when_the_home_page_already_has_novedades(): void
    {
        $novedades = HomeSection::factory()->ofType(HomeSectionType::Latest)->create();
        DB::table('home_sections')->insert(['title' => 'Destacados', 'type' => 'featured', 'products_limit' => 8, 'is_active' => true, 'sort_order' => 2]);

        $this->runFeaturedToNovedadesMigration();

        $this->assertSame([$novedades->id], HomeSection::query()->pluck('id')->all());
    }

    public function test_a_category_section_shows_products_of_that_category_in_the_owner_order(): void
    {
        $category = Category::factory()->create();
        $second = Product::factory()->for($category)->create(['sort_order' => 2]);
        $first = Product::factory()->for($category)->create(['sort_order' => 1]);
        Product::factory()->create();

        $section = HomeSection::factory()->forCategory($category)->create();

        $this->assertEquals([$first->id, $second->id], $section->products()->modelKeys());
    }

    public function test_a_category_section_needs_a_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageHomeSections::class)
            ->callAction('create', data: [
                'title' => 'Stickers Manicure',
                'type' => HomeSectionType::Category->value,
            ])
            ->assertHasFormErrors(['category_id' => 'required']);

        $this->assertDatabaseCount('home_sections', 0);
    }

    public function test_the_owner_adds_a_category_section(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create();

        Livewire::test(ManageHomeSections::class)
            ->callAction('create', data: [
                'title' => 'Stickers Manicure',
                'type' => HomeSectionType::Category->value,
                'category_id' => $category->id,
            ])
            ->assertHasNoFormErrors();

        $section = HomeSection::query()->sole();
        $this->assertSame(HomeSectionType::Category, $section->type);
        $this->assertTrue($section->category->is($category));
    }

    public function test_the_section_form_counts_the_visible_products_of_the_chosen_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $lamps = Category::factory()->create(['name' => 'Lámparas']);
        Product::factory()->for($lamps)->count(2)->create();
        Product::factory()->for($lamps)->hidden()->create();
        $switchedOff = Category::factory()->hidden()->create(['name' => 'Accesorios']);
        Product::factory()->for($switchedOff)->create();

        Livewire::test(ManageHomeSections::class)
            ->mountAction('create')
            ->fillForm(['type' => HomeSectionType::Category->value, 'category_id' => $lamps->id])
            ->assertFormFieldExists('category_id', $this->helperTextIs('Productos visibles en Lámparas: 2.'))
            ->fillForm(['category_id' => $switchedOff->id])
            ->assertFormFieldExists('category_id', $this->helperTextIs('Productos visibles en Accesorios: 1. La categoría está apagada, así que la sección no sale en la portada.'));
    }

    public function test_the_section_form_counts_the_visible_products_on_sale_and_marked_as_new(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Product::factory()->onSale()->count(2)->create();
        Product::factory()->onSale()->hidden()->create();

        Livewire::test(ManageHomeSections::class)
            ->mountAction('create')
            ->fillForm(['type' => HomeSectionType::Offers->value])
            ->assertFormFieldExists('type', $this->helperTextIs('Productos visibles en oferta: 2.'))
            ->fillForm(['type' => HomeSectionType::Latest->value])
            ->assertFormFieldExists('type', $this->helperTextIs('Productos visibles marcados como novedad: 0. Sin productos visibles, la sección no sale en la portada.'));
    }

    public function test_the_sections_open_ready_to_drag_and_the_new_order_is_saved(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $first = HomeSection::factory()->create(['sort_order' => 1]);
        $second = HomeSection::factory()->create(['sort_order' => 2]);

        Livewire::test(ManageHomeSections::class)
            ->assertSet('isTableReordering', true)
            ->call('toggleTableReordering')
            ->assertSet('isTableReordering', true)
            ->call('reorderTable', [(string) $second->id, (string) $first->id]);

        $this->assertSame([$second->id, $first->id], HomeSection::query()->orderBy('sort_order')->pluck('id')->all());
    }

    public function test_a_section_is_still_edited_while_the_list_is_draggable(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $section = HomeSection::factory()->create(['title' => 'Novedades']);

        Livewire::test(ManageHomeSections::class)
            ->callTableAction('edit', $section, data: ['title' => 'Lo nuevo de Hade'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Lo nuevo de Hade', $section->refresh()->title);
    }

    public function test_categories_grid_sections_are_removed(): void
    {
        $offers = HomeSection::factory()->ofType(HomeSectionType::Offers)->create();
        DB::table('home_sections')->insert(['title' => 'Categorías', 'type' => 'categories', 'products_limit' => 8, 'is_active' => true, 'sort_order' => 2]);

        (require database_path('migrations/2026_11_03_094833_remove_categories_grid_home_sections.php'))->up();

        $this->assertSame([$offers->id], HomeSection::query()->pluck('id')->all());
    }

    /**
     * @return Closure(Field): bool
     */
    private function helperTextIs(string $expected): Closure
    {
        return function (Field $field) use ($expected): bool {
            $helperText = $field->getChildSchema(Field::BELOW_CONTENT_SCHEMA_KEY)?->getComponents()[0] ?? null;

            return $helperText instanceof Text && $helperText->getContent() === $expected;
        };
    }

    private function runFeaturedToNovedadesMigration(): void
    {
        (require database_path('migrations/2026_11_03_082647_move_featured_home_sections_to_novedades.php'))->up();
    }
}
