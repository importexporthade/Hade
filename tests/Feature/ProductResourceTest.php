<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_the_owner_creates_a_product_on_sale(): void
    {
        $category = Category::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Kit Hade Recargada',
                'category_id' => $category->id,
                'price' => '76.90',
                'compare_at_price' => '90.00',
            ])
            ->assertSchemaStateSet(['slug' => 'kit-hade-recargada'])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(ProductResource::getUrl('index'));

        $product = Product::query()->sole();
        $this->assertSame('kit-hade-recargada', $product->slug);
        $this->assertTrue($product->category->is($category));
        $this->assertSame(15, $product->discountPercentage());
        $this->assertTrue($product->is_visible);
    }

    public function test_the_regular_price_must_be_higher_than_the_sale_price(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Top Coat',
                'category_id' => Category::factory()->create()->id,
                'price' => '12.00',
                'compare_at_price' => '10.00',
            ])
            ->call('create')
            ->assertHasFormErrors(['compare_at_price' => 'gt']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_errors_are_shown_in_spanish_inside_the_form_not_by_the_browser(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertSeeHtml('novalidate')
            ->fillForm(['name' => '', 'price' => 'abc'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'price' => 'numeric'])
            ->assertSee('Completa nombre.')
            ->assertSee('Precio de venta debe ser un número.');
    }

    public function test_two_products_cannot_share_a_link(): void
    {
        Product::factory()->create(['slug' => 'top-coat']);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Top Coat',
                'slug' => 'top-coat',
                'category_id' => Category::factory()->create()->id,
                'price' => '12.00',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_editing_keeps_the_link_when_the_name_changes(): void
    {
        $product = Product::factory()->create(['name' => 'Midnigth', 'slug' => 'midnigth']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['name' => 'Midnight', 'price' => '35.00'])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('Midnight', $product->name);
        $this->assertSame('midnigth', $product->slug);
    }

    public function test_the_list_filters_products_on_sale(): void
    {
        $onSale = Product::factory()->onSale()->create();
        $regular = Product::factory()->create();

        Livewire::test(ListProducts::class)
            ->filterTable('on_sale')
            ->assertCanSeeTableRecords([$onSale])
            ->assertCanNotSeeTableRecords([$regular]);
    }

    public function test_the_list_sorts_by_the_size_of_the_discount(): void
    {
        $small = Product::factory()->onSale(price: 90.00, compareAtPrice: 100.00)->create();
        $big = Product::factory()->onSale(price: 50.00, compareAtPrice: 100.00)->create();
        $regular = Product::factory()->create();

        Livewire::test(ListProducts::class)
            ->sortTable('discount', 'desc')
            ->assertCanSeeTableRecords([$big, $small, $regular], inOrder: true);
    }

    public function test_the_owner_puts_several_products_on_sale_at_once(): void
    {
        $regular = Product::factory()->create(['price' => 40.00]);
        $alreadyOnSale = Product::factory()->onSale(price: 35.00, compareAtPrice: 50.00)->create();

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('applyDiscount', [$regular, $alreadyOnSale], data: ['percentage' => 20])
            ->assertHasNoFormErrors();

        $this->assertSame(['32.00', '40.00'], [$regular->refresh()->price, $regular->compare_at_price]);
        $this->assertSame(['40.00', '50.00'], [$alreadyOnSale->refresh()->price, $alreadyOnSale->compare_at_price]);
    }

    public function test_a_discount_must_be_between_one_and_ninety_percent(): void
    {
        $product = Product::factory()->create(['price' => 40.00]);

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('applyDiscount', [$product], data: ['percentage' => 95])
            ->assertHasFormErrors(['percentage' => 'max']);

        $this->assertNull($product->refresh()->compare_at_price);
    }

    public function test_the_owner_ends_a_sale_from_the_list(): void
    {
        $product = Product::factory()->onSale(price: 76.90, compareAtPrice: 90.00)->create();

        Livewire::test(ListProducts::class)
            ->callTableBulkAction('removeDiscount', [$product]);

        $product->refresh();
        $this->assertSame('90.00', $product->price);
        $this->assertNull($product->compare_at_price);
    }

    public function test_a_switch_in_the_list_saves_at_once_without_a_notice(): void
    {
        $product = Product::factory()->create(['is_in_stock' => true]);

        Livewire::test(ListProducts::class)
            ->call('updateTableColumnState', 'is_in_stock', (string) $product->id, false)
            ->assertNotNotified();

        $this->assertFalse($product->refresh()->is_in_stock);
    }
}
