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

    public function test_the_editorial_sections_always_show_without_catalog_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                'Tu ritual Hade',
                'Lista para encontrar tu próximo favorito',
                'Tu estilo Hade',
            ]);
    }

    public function test_the_editorial_sections_keep_fixed_slots_between_the_owner_sections(): void
    {
        foreach (['Primera sección', 'Segunda sección', 'Tercera sección', 'Cuarta sección', 'Quinta sección'] as $position => $title) {
            $category = Category::factory()->create(['name' => "Categoría {$position}"]);
            Product::factory()->for($category)->create();
            HomeSection::factory()->forCategory($category)->create([
                'title' => $title,
                'sort_order' => $position + 1,
            ]);
        }

        Page::factory()->create([
            'slug' => 'preguntas-frecuentes',
            'content' => '<h2>¿Pregunta al final?</h2><p>Respuesta al final.</p>',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder([
                'Primera sección',
                'Tu ritual Hade',
                'Segunda sección',
                'Lista para encontrar tu próximo favorito',
                'Tercera sección',
                'Tu estilo Hade',
                'Cuarta sección',
                'Quinta sección',
                'Resolvemos tus dudas',
                '© '.now()->year.' Hade',
            ]);
    }

    public function test_the_home_page_shows_the_active_sections_in_the_owner_order(): void
    {
        $manicure = Category::factory()->create(['name' => 'Stickers Manicure']);
        Product::factory()->for($manicure)->create(['name' => 'Crepusculo']);
        Product::factory()->onSale()->create(['name' => 'Kit Hade Recargada']);

        HomeSection::factory()->forCategory($manicure)->create(['title' => 'El arte en tus manos', 'sort_order' => 2]);
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['title' => 'Favoritos que combinan', 'sort_order' => 1]);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Sección apagada', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Favoritos que combinan', 'Kit Hade Recargada', 'Stickers Manicure', 'El arte en tus manos', 'Crepusculo'])
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

    public function test_novedades_still_shows_when_all_categories_are_hidden_but_their_sections_do_not(): void
    {
        $manicure = Category::factory()->hidden()->create(['name' => 'Stickers Manicure']);
        Product::factory()->for($manicure)->markedAsNew()->create(['name' => 'Novedad de categoría oculta']);
        Product::factory()->for($manicure)->create(['name' => 'Producto de categoría oculta']);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Nuevos hallazgos', 'sort_order' => 1]);
        HomeSection::factory()->forCategory($manicure)->create(['title' => 'El arte en tus manos', 'sort_order' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Así de fácil. Así de Hade.', 'Pequeños detalles, grandes expresiones.', 'El detalle está en ti'])
            ->assertSee('Nuevos hallazgos')
            ->assertSee('Novedad de categoría oculta')
            ->assertDontSee('Producto de categoría oculta')
            ->assertDontSee('El arte en tus manos');
    }

    public function test_the_novedades_section_hides_only_when_no_product_is_marked_as_new(): void
    {
        Product::factory()->create(['name' => 'Producto sin marcar']);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Nuevos hallazgos']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Nuevos hallazgos');
    }

    public function test_a_new_product_in_a_hidden_category_opens_its_page(): void
    {
        $product = Product::factory()->for(Category::factory()->hidden())->markedAsNew()->create();

        $this->get(route('products.show', $product))->assertOk();
    }

    public function test_the_latest_section_offers_in_stock_products_to_add_to_the_cart(): void
    {
        Product::factory()->markedAsNew()->create(['name' => 'Stickers Aurora', 'slug' => 'stickers-aurora']);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Nuevos hallazgos']);

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
            Product::factory()->markedAsNew()->create([
                'name' => "Novedad {$position}",
                'slug' => "novedad-{$position}",
                'created_at' => now()->subDays(10 - $position),
            ]);
        }
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Nuevos hallazgos', 'products_limit' => 8]);

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

        HomeSection::factory()->forCategory($manicure)->create(['title' => 'El arte en tus manos', 'products_limit' => 8]);
        HomeSection::factory()->forCategory($pedicure)->create(['title' => 'Pies que enamoran', 'products_limit' => 8]);

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
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['title' => 'Favoritos que combinan']);

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
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['products_limit' => 8]);

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
        Product::factory()->onSale()->markedAsNew()->create(['name' => 'Kit Primavera', 'slug' => 'kit-primavera']);
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['title' => 'Favoritos que combinan', 'sort_order' => 1]);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Nuevos hallazgos', 'sort_order' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Favoritos que combinan', 'Así de fácil. Así de Hade.', 'Nuevos hallazgos'])
            ->assertSeeInOrder(['Prepara', 'Elige y pega', 'Recorta y lima', 'Cura y disfruta'])
            ->assertSee('Encuentra tu diseño');
    }

    public function test_every_product_section_shares_the_same_design_with_the_title_from_the_manager(): void
    {
        $lamps = Category::factory()->create(['name' => 'Lámparas']);
        Product::factory()->for($lamps)->create(['name' => 'Lámpara Glow']);
        Product::factory()->markedAsNew()->create(['name' => 'Kit Novedad']);

        HomeSection::factory()->forCategory($lamps)->create(['title' => 'Luz para tu ritual', 'sort_order' => 1]);
        HomeSection::factory()->ofType(HomeSectionType::Latest)->create(['title' => 'Lo más pedido', 'sort_order' => 2]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Lámparas', 'Luz para tu ritual', 'Lámpara Glow', 'Agregar al carrito', 'Novedades', 'Lo más pedido', 'Kit Novedad', 'Agregar al carrito'])
            ->assertSee('tracking-[0.24em] uppercase text-rose-light">Lámparas</p>', false)
            ->assertSee('aria-label="Ver más de Luz para tu ritual"', false)
            ->assertSee('aria-label="Ver más de Lo más pedido"', false)
            ->assertSee('href="'.route('categories.show', $lamps).'"', false);
    }

    public function test_product_sections_start_green_after_categories_and_keep_alternating(): void
    {
        foreach (['Lámparas', 'Accesorios', 'Kits'] as $position => $name) {
            $category = Category::factory()->create(['name' => $name]);
            Product::factory()->for($category)->create();
            HomeSection::factory()->forCategory($category)->create(['title' => $name, 'sort_order' => $position]);
        }

        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('/<section class="(scalloped-section z-10 bg-cream|bg-forest) /', $html, $backgrounds);

        $this->assertSame(['bg-forest', 'scalloped-section z-10 bg-cream', 'bg-forest'], $backgrounds[1]);
    }

    public function test_offers_away_from_the_start_leaves_green_after_categories_and_cream_before_the_footer(): void
    {
        foreach (['Lámparas', 'Accesorios', 'Kits'] as $position => $name) {
            $category = Category::factory()->create(['name' => $name]);
            Product::factory()->for($category)->create();
            HomeSection::factory()->forCategory($category)->create([
                'title' => $name,
                'sort_order' => $position === 0 ? 1 : $position + 2,
            ]);
        }

        Product::factory()->onSale()->create();
        HomeSection::factory()->ofType(HomeSectionType::Offers)->create(['sort_order' => 2]);

        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('/<section class="(scalloped-section z-10 bg-cream|bg-forest) /', $html, $backgrounds);

        $this->assertSame(['bg-forest', 'scalloped-section z-10 bg-cream', 'scalloped-section z-10 bg-cream'], $backgrounds[1]);
    }

    public function test_a_section_label_is_left_out_when_the_manager_title_repeats_it(): void
    {
        $lamps = Category::factory()->create(['name' => 'Lámparas']);
        Product::factory()->for($lamps)->create();
        HomeSection::factory()->forCategory($lamps)->create(['title' => 'Lámparas']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<h2 id="seccion-', false)
            ->assertDontSee('tracking-[0.24em] uppercase text-emerald">Lámparas</p>', false)
            ->assertDontSee('tracking-[0.24em] uppercase text-rose-light">Lámparas</p>', false);
    }

    public function test_the_latest_section_does_not_offer_out_of_stock_products_to_add_to_the_cart(): void
    {
        Product::factory()->markedAsNew()->create([
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

    public function test_orders_on_whatsapp_start_with_the_owner_opening_message(): void
    {
        StoreSetting::current()->update(['whatsapp_number' => '51 986 000 362', 'whatsapp_message' => ' Hola Hade, quiero hacer un pedido ']);
        $product = Product::factory()->create(['name' => 'Kit Hade Recargada', 'slug' => 'kit-hade-recargada', 'price' => 76.90]);

        $this->get('/product/kit-hade-recargada')
            ->assertOk()
            ->assertSee('https://wa.me/51986000362?text='.rawurlencode("Hola Hade, quiero hacer un pedido\n\nKit Hade Recargada (S/ 76.90)\n".route('products.show', $product)), false)
            ->assertSee('data-whatsapp-greeting="Hola Hade, quiero hacer un pedido"', false);
    }

    public function test_a_product_page_shows_the_shipping_the_owner_wrote(): void
    {
        StoreSetting::current()->update(['shipping' => 'Lima: delivery S/ 10.
Provincia: por <b>Shalom</b> desde S/ 15.']);
        Product::factory()->create(['slug' => 'kit']);

        $this->get('/product/kit')
            ->assertOk()
            ->assertSeeInOrder(['aria-labelledby="despacho"', 'Envíos y delivery', 'Llega a Lima y provincia', 'Lima: delivery S/ 10.
Provincia: por &lt;b&gt;Shalom&lt;/b&gt; desde S/ 15.'], false)
            ->assertDontSee('se confirman según tu ubicación');
    }

    public function test_without_a_shipping_text_the_product_page_shows_a_general_one(): void
    {
        Product::factory()->create(['slug' => 'kit']);

        $this->get('/product/kit')
            ->assertSeeInOrder(['Envíos y delivery', 'Llega a Lima y provincia', 'Hacemos delivery en Lima y enviamos a todo el Perú.']);
    }

    public function test_a_product_says_which_destinations_it_ships_to(): void
    {
        Product::factory()->create(['slug' => 'solo-lima', 'ships_to_province' => false]);
        Product::factory()->create(['slug' => 'solo-provincia', 'ships_to_capital' => false]);

        $this->get('/product/solo-lima')->assertSee('Llega a Lima')->assertDontSee('Llega a Lima y provincia');
        $this->get('/product/solo-provincia')->assertSee('Llega a provincia')->assertDontSee('Llega a Lima');
    }

    public function test_a_product_shipped_nowhere_leaves_the_section_out(): void
    {
        StoreSetting::current()->update(['shipping' => 'Delivery S/ 10.']);
        Product::factory()->create(['slug' => 'kit', 'ships_to_capital' => false, 'ships_to_province' => false]);

        $this->get('/product/kit')
            ->assertOk()
            ->assertDontSee('aria-labelledby="despacho"', false)
            ->assertDontSee('Delivery S/ 10.');
    }

    public function test_the_shipping_details_link_shows_only_once_that_page_is_published(): void
    {
        Product::factory()->create(['slug' => 'kit']);
        $detailsLink = 'href="'.route('pages.show', 'envios-y-delivery').'"';

        $this->get('/product/kit')->assertDontSee('Ver detalles');

        Page::factory()->create(['slug' => 'envios-y-delivery']);

        $this->get('/product/kit')->assertSee('Ver detalles')->assertSee($detailsLink, false);
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

    public function test_the_novedades_page_lists_visible_products_marked_as_new_from_newest_to_oldest(): void
    {
        Product::factory()->markedAsNew()->create([
            'name' => 'Diseño anterior',
            'created_at' => now()->subDays(2),
        ]);
        Product::factory()->markedAsNew()->create([
            'name' => 'Diseño reciente',
            'created_at' => now()->subDay(),
        ]);
        Product::factory()->markedAsNew()->hidden()->create([
            'name' => 'Diseño oculto',
            'created_at' => now(),
        ]);
        Product::factory()->create([
            'name' => 'Diseño de siempre',
            'is_featured' => false,
            'created_at' => now(),
        ]);

        $this->get(route('products.index', ['collection' => 'latest']))
            ->assertOk()
            ->assertSee('Recién llegados')
            ->assertSee('Novedades')
            ->assertSeeInOrder(['Diseño reciente', 'Diseño anterior'])
            ->assertDontSee('Diseño oculto')
            ->assertDontSee('Diseño de siempre');
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

    public function test_an_empty_catalog_does_not_link_to_itself_while_an_empty_search_links_back_to_it(): void
    {
        // The menu also says "Ver todos los productos", so only the empty-state button tells the pages apart
        $catalog = $this->get('/products')->assertOk()->assertSee('Aún no hay productos disponibles.')->getContent();
        $search = $this->get('/products?q=perfume')->assertOk()->getContent();

        $this->assertSame(substr_count($catalog, 'Ver todos los productos') + 1, substr_count($search, 'Ver todos los productos'));
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

    public function test_the_cart_asks_for_the_dni_and_the_address_in_lima_or_the_agency_in_the_provinces(): void
    {
        $this->get('/products')
            ->assertOk()
            ->assertSeeInOrder(['name="first_name"', 'name="last_name"', 'name="dni"', 'name="shipping"'], false)
            ->assertSeeInOrder(['data-cart-shipping-fields="lima"', 'name="district"', 'name="address"', 'name="reference"'], false)
            ->assertSeeInOrder(['data-cart-shipping-fields="provincia" hidden disabled', 'name="agency"'], false)
            ->assertDontSee('name="note"', false)
            ->assertDontSee('name="place"', false);
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
