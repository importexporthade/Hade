<?php

namespace Tests\Feature;

use App\Enums\HomeSectionType;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_without_products_is_hidden_from_the_store(): void
    {
        $category = Category::factory()->create(['name' => 'Accesorios']);

        $this->get(route('categories.show', $category))->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('Accesorios');
    }

    public function test_a_category_whose_products_are_all_hidden_is_hidden_until_one_is_shown(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->hidden()->create();
        Product::factory()->for($category)->hidden()->create();

        $this->get(route('categories.show', $category))->assertNotFound();

        $product->update(['is_visible' => true]);

        $this->get(route('categories.show', $category))->assertOk();
    }

    public function test_a_category_with_some_hidden_products_is_still_shown(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->create();
        Product::factory()->for($category)->hidden()->create();

        $this->get(route('categories.show', $category))->assertOk();
    }

    public function test_a_category_hides_itself_when_its_products_are_deleted(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $product->delete();

        $this->get(route('categories.show', $category))->assertNotFound();
    }

    public function test_the_switch_still_hides_a_category_with_visible_products(): void
    {
        $category = Category::factory()->hidden()->create();
        Product::factory()->for($category)->create();

        $this->get(route('categories.show', $category))->assertNotFound();
    }

    public function test_a_category_section_leaves_the_home_page_when_its_category_hides_itself(): void
    {
        $category = Category::factory()->create();
        Product::factory()->for($category)->hidden()->create(['name' => 'Lámpara oculta']);
        HomeSection::factory()->forCategory($category)->create(['title' => 'Lámparas UV']);

        $this->get('/')->assertOk()->assertDontSee('Lámparas UV');
    }

    public function test_the_section_form_lists_every_category_and_marks_the_hidden_ones(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $lamps = Category::factory()->create(['name' => 'Lámparas']);
        Product::factory()->for($lamps)->create();
        $accessories = Category::factory()->create(['name' => 'Accesorios']);

        Livewire::test(ManageHomeSections::class)
            ->mountAction('create')
            ->fillForm(['type' => HomeSectionType::Category->value])
            ->assertFormFieldExists('category_id', function (Select $field) use ($lamps, $accessories): bool {
                $options = $field->getOptions();
                ksort($options);

                return $options === [$lamps->id => 'Lámparas', $accessories->id => 'Accesorios (oculta)'];
            });
    }

    public function test_the_category_list_says_when_a_category_hid_itself(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Category::factory()->create(['name' => 'Accesorios']);

        Livewire::test(ManageCategories::class)
            ->assertSee('Oculta: no tiene productos visibles');
    }
}
