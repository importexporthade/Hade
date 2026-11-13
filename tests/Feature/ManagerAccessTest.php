<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Widgets\CatalogOverview;
use App\Models\Product;
use App\Models\User;
use Filament\Widgets\AccountWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_manager_login(): void
    {
        $this->get('/manager')->assertRedirect('/manager/login');
    }

    public function test_accounts_that_are_not_the_owner_cannot_open_the_manager(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/manager')
            ->assertForbidden();
    }

    public function test_the_owner_opens_the_manager_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/manager')
            ->assertOk()
            ->assertSeeLivewire(CatalogOverview::class)
            ->assertSeeInOrder(['<title>', 'Dashboard', '</title>'], false)
            ->assertDontSeeLivewire(AccountWidget::class)
            ->assertDontSee('Escritorio');
    }

    public function test_every_manager_screen_links_to_the_store_in_a_new_tab(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['/manager', '/manager/products'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('href="'.route('home').'"', false)
                ->assertSee('target="_blank"', false)
                ->assertSee('Ver tienda');
        }
    }

    public function test_the_dashboard_counts_the_catalog(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Product::factory()->onSale()->create();
        Product::factory()->create(['is_in_stock' => false]);
        Product::factory()->hidden()->create();

        Livewire::test(CatalogOverview::class)
            ->assertSeeInOrder(['Productos en la tienda', '2', '1 ocultos', 'En oferta', '1', 'Sin stock', '1']);
    }

    /**
     * Every screen of the manager renders for the owner.
     */
    public function test_the_owner_opens_every_manager_screen(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['products', 'products/create', 'categories', 'banners', 'banners/create', 'home-sections', 'pages', 'pages/create', 'tienda'] as $path) {
            $this->get("/manager/{$path}")->assertOk();
        }
    }

    /**
     * The breadcrumbs lead back to the table, with no empty step and no link to the page
     * already open.
     */
    public function test_the_breadcrumbs_lead_from_each_screen_back_to_its_table(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['name' => 'Kit Hade Recargada']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertSeeInOrder([route('filament.manager.resources.products.index'), 'Productos', 'Editar'], false)
            ->assertDontSee('href="'.route('filament.manager.resources.products.edit', $product).'"', false);

        Livewire::test(ManageCategories::class)
            ->assertSeeInOrder([route('filament.manager.resources.categories.index'), 'Categorías', 'Listado'], false);
    }
}
