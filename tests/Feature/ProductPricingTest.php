<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\Price;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProductPricingTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([76.90, 90.00, 15])]
    #[TestWith([119.90, 177.00, 32])]
    #[TestWith([59.90, 76.00, 21])]
    public function test_the_discount_badge_matches_the_store(float $price, float $compareAtPrice, int $discount): void
    {
        $product = Product::factory()->onSale($price, $compareAtPrice)->create();

        $this->assertTrue($product->isOnSale());
        $this->assertSame($discount, $product->discountPercentage());
    }

    public function test_a_product_without_a_higher_regular_price_is_not_on_sale(): void
    {
        $regular = Product::factory()->create(['price' => 12.00]);
        $samePrice = Product::factory()->create(['price' => 12.00, 'compare_at_price' => 12.00]);

        $this->assertNull($regular->discountPercentage());
        $this->assertFalse($samePrice->isOnSale());
        $this->assertSame([], Product::query()->onSale()->pluck('id')->all());
    }

    public function test_removing_a_discount_from_a_regular_product_leaves_its_price(): void
    {
        $product = Product::factory()->create(['price' => 12.00]);

        $product->removeDiscount();

        $this->assertSame('12.00', $product->refresh()->price);
    }

    public function test_prices_are_shown_in_soles(): void
    {
        $this->assertSame('S/ 1,290.50', Price::format('1290.5'));
        $this->assertNull(Price::format(null));
    }
}
