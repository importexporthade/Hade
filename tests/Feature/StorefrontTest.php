<?php

namespace Tests\Feature;

use App\Enums\HomeSectionType;
use App\Enums\PageVisibility;
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
            ->assertSeeInOrder(['Kits y promociones', 'Kit Hade Recargada', 'Stickers manicure', 'El arte en tus manos', 'Crepusculo'])
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

    public function test_the_latest_section_offers_in_stock_products_to_add_to_the_cart(): void
    {
        Product::factory()->create(['name' => 'Stickers Aurora', 'slug' => 'stickers-aurora']);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Novedades']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Novedades')
            ->assertSee('Nuevos hallazgos')
            ->assertSee('Stickers Aurora')
            ->assertSee('Pequeños detalles, grandes expresiones.')
            ->assertSee('Entrar a la tienda')
            ->assertSee('Ver novedades')
            ->assertSee('data-add-to-cart', false)
            ->assertSee('Agregar al carrito');
    }

    public function test_the_latest_section_shows_five_products_in_an_autoplay_mobile_carousel_and_desktop_grid(): void
    {
        foreach (range(1, 6) as $position) {
            Product::factory()->create([
                'name' => "Novedad {$position}",
                'slug' => "novedad-{$position}",
            ]);
        }
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['products_limit' => 8]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Novedad 6', 'Novedad 5', 'Novedad 4', 'Novedad 3', 'Novedad 2'])
            ->assertDontSee('Novedad 1')
            ->assertSee('data-carousel-autoplay', false)
            ->assertSee('data-carousel-interval="5000"', false)
            ->assertSee('lg:grid-cols-5', false)
            ->assertSee('aria-label="Ver más de Nuevos hallazgos"', false)
            ->assertSee('href="'.route('products.index', ['collection' => 'latest']).'"', false);
    }

    public function test_the_manicure_and_pedicure_sections_show_five_products_in_autoplay_mobile_carousels_and_desktop_grids(): void
    {
        $manicure = Category::factory()->create(['name' => 'Stickers Manicure']);
        $pedicure = Category::factory()->create(['name' => 'Stickers Pedicure']);

        foreach (range(1, 6) as $position) {
            Product::factory()->for($manicure)->create([
                'name' => "Manicure {$position}",
                'slug' => "manicure-{$position}",
                'sort_order' => $position,
            ]);
            Product::factory()->for($pedicure)->create([
                'name' => "Pedicure {$position}",
                'slug' => "pedicure-{$position}",
                'sort_order' => $position,
            ]);
        }

        HomeSection::factory()->forCategory($manicure)->create(['products_limit' => 8]);
        HomeSection::factory()->forCategory($pedicure)->create(['products_limit' => 8]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Manicure 1', 'Manicure 2', 'Manicure 3', 'Manicure 4', 'Manicure 5'])
            ->assertSeeInOrder(['Pedicure 1', 'Pedicure 2', 'Pedicure 3', 'Pedicure 4', 'Pedicure 5'])
            ->assertDontSee('Manicure 6')
            ->assertDontSee('Pedicure 6')
            ->assertSee('data-carousel-autoplay', false)
            ->assertSee('data-carousel-interval="5000"', false)
            ->assertSee('lg:grid-cols-5', false)
            ->assertSee('aria-label="Ver más de El arte en tus manos"', false)
            ->assertSee('aria-label="Ver más de Pies que enamoran"', false)
            ->assertSee('href="'.route('categories.show', $manicure).'"', false)
            ->assertSee('href="'.route('categories.show', $pedicure).'"', false);
    }

    public function test_the_offers_section_offers_in_stock_products_to_add_to_the_cart(): void
    {
        Product::factory()->onSale()->create(['name' => 'Kit Esencial', 'slug' => 'kit-esencial']);
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['title' => 'Ofertas']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Favoritos que combinan')
            ->assertSee('Kit Esencial')
            ->assertSee('data-add-to-cart', false)
            ->assertSee('Agregar al carrito');
    }

    public function test_the_offers_section_renders_as_an_automatic_carousel_with_pagination(): void
    {
        foreach (range(1, 6) as $position) {
            Product::factory()->onSale()->create([
                'name' => "Oferta {$position}",
                'slug' => "oferta-{$position}",
                'sort_order' => $position,
            ]);
        }
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['products_limit' => 3]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Oferta 1')
            ->assertSee('Oferta 5')
            ->assertDontSee('Oferta 6')
            ->assertSee('data-carousel-autoplay', false)
            ->assertSee('data-carousel-interval="5000"', false)
            ->assertSee('data-carousel-merge-last-page', false)
            ->assertSee('data-carousel-pagination', false)
            ->assertSee('aria-label="Ver todos los productos"', false)
            ->assertDontSee('data-carousel-progress', false);
    }

    public function test_the_how_it_works_section_renders_between_offers_and_latest_products(): void
    {
        Product::factory()->onSale()->create(['name' => 'Kit Primavera', 'slug' => 'kit-primavera']);
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['sort_order' => 1]);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['sort_order' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Favoritos que combinan', 'Así de fácil. Así de Hade.', 'Nuevos hallazgos'])
            ->assertSeeInOrder(['Prepara', 'Elige y pega', 'Recorta y lima', 'Cura y disfruta'])
            ->assertSee('Encuentra tu diseño');
    }

    public function test_the_latest_section_does_not_offer_out_of_stock_products_to_add_to_the_cart(): void
    {
        Product::factory()->create([
            'name' => 'Stickers Eclipse',
            'slug' => 'stickers-eclipse',
            'is_in_stock' => false,
        ]);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Novedades']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Stickers Eclipse')
            ->assertSee('Agotado')
            ->assertDontSee('data-add-to-cart', false)
            ->assertDontSee('Agregar al carrito');
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

    public function test_the_home_hero_slides_through_the_showing_banners_in_the_owner_order(): void
    {
        Banner::factory()->create(['title' => 'Colección de verano', 'sort_order' => 2]);
        Banner::factory()->create(['title' => 'Nuevos diseños', 'sort_order' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-hero-slider', false)
            ->assertSeeInOrder(['Ir a la diapositiva 1: Nuevos diseños', 'Ir a la diapositiva 2: Colección de verano'])
            ->assertDontSee('Diseños que se aplican en minutos');
    }

    public function test_a_single_banner_shows_as_a_still_hero_without_slider_controls(): void
    {
        Banner::factory()->create(['title' => 'Promo de octubre']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Promo de octubre')
            ->assertDontSee('data-hero-slider', false)
            ->assertDontSee('Ir a la diapositiva');
    }

    public function test_without_banners_the_home_shows_the_brand_hero(): void
    {
        Banner::factory()->inactive()->create(['title' => 'Promo apagada']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Diseños que se aplican en minutos')
            ->assertDontSee('data-hero-slider', false);
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
            ->assertSee('Agotado: este producto no tiene stock por ahora.')
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

    public function test_the_latest_products_page_orders_visible_products_from_newest_to_oldest(): void
    {
        Product::factory()->create([
            'name' => 'Diseño anterior',
            'created_at' => now()->subDays(2),
        ]);
        Product::factory()->create([
            'name' => 'Diseño reciente',
            'created_at' => now()->subDay(),
        ]);
        Product::factory()->hidden()->create([
            'name' => 'Diseño oculto',
            'created_at' => now(),
        ]);

        $this->get(route('products.index', ['collection' => 'latest']))
            ->assertOk()
            ->assertSee('Recién llegados')
            ->assertSee('Novedades')
            ->assertSeeInOrder(['Diseño reciente', 'Diseño anterior'])
            ->assertDontSee('Diseño oculto');
    }

    public function test_the_navigation_links_to_the_latest_products_page(): void
    {
        $latestProductsUrl = route('products.index', ['collection' => 'latest']);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.$latestProductsUrl.'"', false)
            ->assertSee('Novedades')
            ->assertDontSee('Más vendidos');
    }

    public function test_paginated_product_results_render_the_store_pagination_and_keep_the_search(): void
    {
        foreach (range(1, 25) as $position) {
            Product::factory()->create([
                'name' => "Glow {$position}",
                'slug' => "glow-{$position}",
                'sort_order' => $position,
            ]);
        }

        $this->get('/products?q=glow')
            ->assertOk()
            ->assertSee('aria-label="Paginación"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Página anterior"', false)
            ->assertSee('aria-label="Página siguiente"', false)
            ->assertSee('q=glow&amp;page=2', false);
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
        Page::factory()->create(['slug' => 'borrador', 'visibility' => PageVisibility::Hidden]);

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
