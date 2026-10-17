<?php

namespace Tests\Feature;

use App\Enums\HomeSectionType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_home_page_shows_the_active_sections_in_the_owner_order(): void
    {
        $manicure = Category::factory()->create(['name' => 'Stickers Manicure']);
        Product::factory()->for($manicure)->create(['name' => 'Crepusculo']);
        Product::factory()->onSale()->create(['name' => 'Kit Hade Recargada']);

        HomeSection::factory()->forCategory($manicure)->create(['title' => 'Stickers Manicure', 'sort_order' => 2]);
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['title' => 'Ofertas', 'sort_order' => 1]);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Sección apagada', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Ofertas', 'Kit Hade Recargada', 'Stickers Manicure', 'Crepusculo'])
            ->assertDontSee('Sección apagada');
    }

    public function test_the_home_page_leaves_out_hidden_products_and_sections_of_hidden_categories(): void
    {
        $hiddenCategory = Category::factory()->hidden()->create();
        Product::factory()->for($hiddenCategory)->create(['name' => 'Producto de categoría oculta']);
        Product::factory()->hidden()->create(['name' => 'Producto oculto']);
        HomeSection::factory()->forCategory($hiddenCategory)->create(['title' => 'Categoría oculta']);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Novedades']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Producto oculto')
            ->assertDontSee('Producto de categoría oculta')
            ->assertDontSee('Categoría oculta');
    }

    public function test_the_home_page_shows_only_banners_that_are_showing(): void
    {
        Banner::factory()->create(['title' => 'Promo de octubre']);
        Banner::factory()->inactive()->create(['title' => 'Promo apagada']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Promo de octubre')
            ->assertDontSee('Promo apagada');
    }

    public function test_a_product_page_shows_the_sale_and_the_whatsapp_order_link(): void
    {
        StoreSetting::current()->update(['whatsapp_number' => '51 986 000 362']);
        $product = Product::factory()->onSale(price: 76.90, compareAtPrice: 90.00)->create([
            'name' => 'Kit Hade Recargada',
            'slug' => 'kit-hade-recargada',
            'description' => '<p>Incluye lámpara recargable</p><script>alert(1)</script>',
        ]);

        $this->get('/product/kit-hade-recargada')
            ->assertOk()
            ->assertSee('S/ 76.90')
            ->assertSee('S/ 90.00')
            ->assertSee('-15 %')
            ->assertSee('¡Ahorra S/ 13.10!')
            ->assertSee('Agregar al carrito')
            ->assertSee('https://wa.me/51986000362?text='.rawurlencode("Hola, quiero pedir: Kit Hade Recargada (S/ 76.90)\n".route('products.show', $product)), false)
            ->assertSee('Incluye lámpara recargable')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_a_product_out_of_stock_cannot_be_added_to_the_cart(): void
    {
        Product::factory()->create(['slug' => 'lampara-conectada', 'is_in_stock' => false]);

        $this->get('/product/lampara-conectada')
            ->assertOk()
            ->assertSee('Este producto está agotado por ahora.')
            ->assertDontSee('Agregar al carrito');
    }

    public function test_hidden_products_and_products_of_hidden_categories_are_not_found(): void
    {
        Product::factory()->hidden()->create(['slug' => 'oculto']);
        Product::factory()->for(Category::factory()->hidden())->create(['slug' => 'en-categoria-oculta']);

        $this->get('/product/oculto')->assertNotFound();
        $this->get('/product/en-categoria-oculta')->assertNotFound();
    }

    public function test_a_category_page_lists_its_visible_products(): void
    {
        $lamps = Category::factory()->create(['name' => 'Lámparas', 'slug' => 'lamparas']);
        Product::factory()->for($lamps)->create(['name' => 'Lámpara Recargable']);
        Product::factory()->for($lamps)->hidden()->create(['name' => 'Lámpara oculta']);
        Product::factory()->create(['name' => 'Top Coat']);

        $this->get('/category/lamparas')
            ->assertOk()
            ->assertSee('Lámpara Recargable')
            ->assertDontSee('Lámpara oculta')
            ->assertDontSee('Top Coat');
    }

    public function test_a_hidden_category_is_not_found(): void
    {
        Category::factory()->hidden()->create(['slug' => 'accesorios']);

        $this->get('/category/accesorios')->assertNotFound();
    }

    public function test_the_search_finds_products_by_name_regardless_of_case(): void
    {
        Product::factory()->create(['name' => 'Kit Glow Recargable']);
        Product::factory()->create(['name' => 'Top Coat']);

        $this->get('/products?q=glow')
            ->assertOk()
            ->assertSee('Kit Glow Recargable')
            ->assertDontSee('Top Coat');
    }

    public function test_a_search_without_results_says_so(): void
    {
        Product::factory()->create(['name' => 'Top Coat']);

        $this->get('/products?q=perfume')
            ->assertOk()
            ->assertSee('No encontramos productos con ese nombre.');
    }

    public function test_a_published_page_shows_its_content_without_scripts(): void
    {
        Page::factory()->create([
            'title' => 'Envíos y delivery',
            'slug' => 'envios-y-delivery',
            'content' => '<p>Delivery a todo el Perú</p><script>alert(1)</script>',
        ]);

        $this->get('/information/envios-y-delivery')
            ->assertOk()
            ->assertSee('Delivery a todo el Perú')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_an_unpublished_page_is_not_found(): void
    {
        Page::factory()->create(['slug' => 'borrador', 'is_published' => false]);

        $this->get('/information/borrador')->assertNotFound();
    }

    public function test_every_page_carries_the_store_details_from_the_manager(): void
    {
        StoreSetting::current()->update([
            'announcement' => 'Envío gratis a todo el Perú',
            'email' => 'import.export.hade@gmail.com',
            'whatsapp_number' => '51 986 000 362',
            'instagram_url' => 'https://www.instagram.com/hade.pe',
        ]);

        $this->get('/products')
            ->assertOk()
            ->assertSee('Envío gratis a todo el Perú')
            ->assertSee('import.export.hade@gmail.com')
            ->assertSee('https://www.instagram.com/hade.pe')
            ->assertSee('data-whatsapp-number="51986000362"', false)
            ->assertSee('Comprar por WhatsApp');
    }
}
