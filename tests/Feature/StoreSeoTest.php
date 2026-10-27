<?php

namespace Tests\Feature;

use App\Enums\PageVisibility;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        StoreSetting::current()->update(['store_name' => 'Hade', 'footer_text' => 'Stickers de uñas en gel con envío gratis a todo el Perú.']);
    }

    public function test_a_shared_product_link_shows_its_photo_price_and_readable_description(): void
    {
        $product = Product::factory()->create([
            'name' => 'Kit Hade Recargada',
            'slug' => 'kit-hade-recargada',
            'price' => 76.90,
            'description' => '<p>Incluye</p><ul><li>1 blister de stickers</li><li>Lámpara&nbsp;recargable</li></ul>',
            'images' => ['products/kit.jpg'],
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('<meta name="description" content="Incluye 1 blister de stickers Lámpara recargable">', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:url" content="'.route('products.show', $product).'">', false)
            ->assertSee('<meta property="product:price:amount" content="76.90">', false)
            ->assertSee('<meta property="product:price:currency" content="PEN">', false)
            ->assertSee('<link rel="canonical" href="'.route('products.show', $product).'">', false)
            ->assertSee('products/kit', false);
    }

    public function test_a_product_without_description_or_photo_still_shares_something_useful(): void
    {
        $product = Product::factory()->create(['name' => 'Lunares', 'price' => 35, 'description' => null, 'images' => []]);

        $this->get(route('products.show', $product))
            ->assertSee('content="Lunares a S/ 35.00. Stickers de uñas en gel', false)
            ->assertSee('<meta property="og:image" content="'.asset('images/hero-nail-stickers.jpg').'">', false)
            ->assertDontSee('product-placeholder.svg">', false);
    }

    public function test_pages_without_their_own_description_use_the_store_one(): void
    {
        $this->get('/')
            ->assertSee('<meta name="description" content="Stickers de uñas en gel con envío gratis a todo el Perú.">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertDontSee('product:price:amount', false);
    }

    public function test_search_results_are_kept_out_of_search_engines(): void
    {
        $this->get('/products?q=kit')
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertDontSee('rel="canonical"', false);

        $this->get('/products')->assertDontSee('noindex', false);
    }

    public function test_the_sitemap_lists_only_what_customers_can_see(): void
    {
        $category = Category::factory()->create(['slug' => 'lamparas']);
        Category::factory()->hidden()->create(['slug' => 'categoria-oculta']);
        Product::factory()->for($category)->create(['slug' => 'lampara-recargable']);
        Product::factory()->hidden()->create(['slug' => 'producto-oculto']);
        Page::factory()->create(['slug' => 'politica-de-privacidad']);
        Page::factory()->create(['slug' => 'borrador', 'visibility' => PageVisibility::Hidden]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
            ->assertSee('<loc>'.route('home').'</loc>', false)
            ->assertSee('<loc>'.route('categories.show', 'lamparas').'</loc>', false)
            ->assertSee('<loc>'.route('products.show', 'lampara-recargable').'</loc>', false)
            ->assertSee('<loc>'.route('pages.show', 'politica-de-privacidad').'</loc>', false)
            ->assertSee('<loc>'.route('complaints.create').'</loc>', false)
            ->assertDontSee('categoria-oculta')
            ->assertDontSee('producto-oculto')
            ->assertDontSee('borrador');
    }

    public function test_robots_keep_crawlers_out_of_the_manager_and_point_to_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /manager')
            ->assertSee('Sitemap: '.route('sitemap'));
    }
}
