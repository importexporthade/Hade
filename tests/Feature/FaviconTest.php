<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_storefront_shows_the_wine_logo_as_its_tab_icon(): void
    {
        $this->withoutVite();

        $this->get('/products')
            ->assertOk()
            ->assertSee(asset('images/logos/favicon-vino-32.png'), false);
    }

    public function test_the_manager_shows_the_same_wine_logo_as_its_tab_icon(): void
    {
        $this->get('/manager/login')
            ->assertOk()
            ->assertSee(asset('images/logos/favicon-vino-32.png'), false);
    }
}
