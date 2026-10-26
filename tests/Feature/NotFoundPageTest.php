<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_an_unknown_address_shows_the_store_not_found_page(): void
    {
        $this->get('/esta-direccion-no-existe')
            ->assertNotFound()
            ->assertSee('Ups... esta página se perdió')
            ->assertSee(route('home'));
    }

    public function test_a_missing_product_shows_the_store_not_found_page(): void
    {
        $this->get(route('products.show', 'producto-inexistente'))
            ->assertNotFound()
            ->assertSee('Ups... esta página se perdió');
    }
}
