<?php

namespace Tests\Feature;

use App\Filament\Pages\Debug;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\User;
use App\Support\AppCaches;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class DebugPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_the_owner_opens_the_debug_page_from_the_manager(): void
    {
        $this->get('/manager/debug')
            ->assertOk()
            ->assertSee('Datos de prueba')
            ->assertSee('Cachés de Laravel');
    }

    public function test_test_data_is_created_hidden_from_the_store_unless_asked(): void
    {
        Livewire::test(Debug::class)
            ->callAction('createTestData', data: ['categories' => 2, 'productsPerCategory' => 5, 'banners' => 1, 'visibleInStore' => false])
            ->assertHasNoActionErrors()
            ->assertNotified('Datos de prueba creados');

        $this->assertSame(2, Category::query()->where('is_visible', false)->count());
        $this->assertSame(10, Product::query()->count());
        $this->assertSame(0, Product::query()->visible()->count());
        $this->assertSame(4, Product::query()->onSale()->count());
        $this->assertSame(2, Product::query()->where('is_in_stock', false)->count());
        $this->assertSame(2, HomeSection::query()->where('is_active', false)->count());
        $banner = Banner::query()->sole();
        $this->assertFalse($banner->is_active);
        $this->assertStringStartsWith('[Prueba] ', $banner->title);
        Storage::disk('public')->assertExists($banner->image);

        Livewire::test(Debug::class)
            ->callAction('createTestData', data: ['categories' => 1, 'productsPerCategory' => 3, 'banners' => 0, 'visibleInStore' => true]);

        $this->assertSame(3, Product::query()->visible()->count());
        $this->get('/products')->assertSee('[Prueba] Clásico 1.1');
    }

    public function test_deleting_test_data_leaves_the_real_catalog_alone(): void
    {
        $realProduct = Product::factory()->for(Category::factory())->create(['name' => 'Kit Hade Recargada']);
        $realBanner = Banner::factory()->create(['title' => 'Promo de verano']);

        Livewire::test(Debug::class)
            ->callAction('createTestData', data: ['categories' => 2, 'productsPerCategory' => 3, 'banners' => 2, 'visibleInStore' => false]);
        $testBannerPhoto = Banner::query()->where('title', 'like', '[Prueba]%')->value('image');

        Livewire::test(Debug::class)
            ->assertSee('Ahora hay 2 categorías, 6 productos, 2 banners y 2 secciones de prueba.')
            ->callAction('deleteTestData')
            ->assertNotified('Datos de prueba borrados');

        $this->assertModelExists($realProduct);
        $this->assertModelExists($realProduct->category);
        $this->assertModelExists($realBanner);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame(1, Category::query()->count());
        $this->assertSame(1, Banner::query()->count());
        $this->assertSame(0, HomeSection::query()->count());
        Storage::disk('public')->assertMissing($testBannerPhoto);

        Livewire::test(Debug::class)
            ->assertSee('No hay datos de prueba.')
            ->assertActionDisabled('deleteTestData');
    }

    public function test_each_cache_button_rebuilds_its_own_cache(): void
    {
        $caches = $this->mock(AppCaches::class);
        $caches->shouldReceive('deployedCommit')->andReturn(null);
        $caches->shouldReceive('areUpToDate')->andReturn(true);
        $caches->shouldReceive('rebuildViews')->once();
        $caches->shouldReceive('rebuildRoutes')->once();
        $caches->shouldReceive('rebuildConfig')->once();

        Livewire::test(Debug::class)
            ->callAction('rebuildViews')
            ->assertNotified('Vistas rehechas')
            ->callAction('rebuildRoutes')
            ->assertNotified('Rutas rehechas')
            ->callAction('rebuildConfig')
            ->assertNotified('Configuración y caché rehechas');
    }

    public function test_the_owner_applies_the_migrations_a_deploy_left_pending(): void
    {
        $latestMigration = pathinfo(collect(File::files(database_path('migrations')))->map->getFilename()->sort()->last(), PATHINFO_FILENAME);

        Livewire::test(Debug::class)
            ->assertSee('Está al día: no hay cambios pendientes.')
            ->assertActionDisabled('runMigrations');

        Artisan::call('migrate:rollback', ['--step' => 1]);

        Livewire::test(Debug::class)
            ->assertSee('1 pendientes: '.$latestMigration)
            ->callAction('runMigrations')
            ->assertNotified('Base de datos al día')
            ->assertSee('Está al día: no hay cambios pendientes.');

        $this->assertSame([], Debug::pendingMigrations());
        $this->assertTrue(Schema::hasColumn('products', 'ships_to_capital'));
    }

    public function test_a_cache_that_cannot_be_rebuilt_says_why(): void
    {
        $caches = $this->mock(AppCaches::class);
        $caches->shouldReceive('deployedCommit')->andReturn(null);
        $caches->shouldReceive('areUpToDate')->andReturn(true);
        $caches->shouldReceive('rebuildRoutes')->andThrow(new RuntimeException('Unable to prepare route [x] for serialization.'));

        Livewire::test(Debug::class)
            ->callAction('rebuildRoutes')
            ->assertNotified('No se pudo terminar');
    }
}
