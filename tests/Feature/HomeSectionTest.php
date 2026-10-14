<?php

namespace Tests\Feature;

use App\Enums\HomeSectionType;
use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_a_featured_section_shows_featured_products(): void
    {
        $featured = Product::factory()->featured()->create();
        Product::factory()->create();

        $section = HomeSection::factory()->ofType(HomeSectionType::Featured)->create();

        $this->assertEquals([$featured->id], $section->products()->modelKeys());
    }

    public function test_a_latest_section_shows_the_newest_products_up_to_its_limit(): void
    {
        $this->travelTo(now()->subDays(2));
        Product::factory()->create();
        $this->travelBack();
        $newer = Product::factory()->count(2)->create();

        $section = HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['products_limit' => 2]);

        $this->assertEqualsCanonicalizing($newer->modelKeys(), $section->products()->modelKeys());
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

    public function test_a_categories_grid_shows_no_products(): void
    {
        Product::factory()->create();

        $section = HomeSection::factory()->ofType(HomeSectionType::Categories)->create();

        $this->assertTrue($section->products()->isEmpty());
    }

    public function test_a_category_section_needs_a_category(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageHomeSections::class)
            ->callAction('create', data: [
                'title' => 'Stickers Manicure',
                'type' => HomeSectionType::Category->value,
                'products_limit' => 8,
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
                'products_limit' => 4,
            ])
            ->assertHasNoFormErrors();

        $section = HomeSection::query()->sole();
        $this->assertSame(HomeSectionType::Category, $section->type);
        $this->assertTrue($section->category->is($category));
        $this->assertSame(4, $section->products_limit);
    }
}
