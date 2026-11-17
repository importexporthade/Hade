<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\FakesTiendada;
use Tests\TestCase;

class ImportTiendadaCommandTest extends TestCase
{
    use FakesTiendada;
    use RefreshDatabase;

    private const string STORE_URL = 'https://tienda.example';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_it_copies_products_categories_photos_logo_and_header(): void
    {
        $this->fakeTiendada(self::STORE_URL);

        $this->artisan('hade:import-tiendada', ['--url' => self::STORE_URL, '--store' => 'S-1'])
            ->expectsOutputToContain('Productos nuevos: 2. Productos completados: 0. Fotos descargadas: 6.')
            ->assertSuccessful();

        $kits = Category::query()->where('slug', 'kits-y-promociones')->sole();
        Storage::disk('public')->assertExists($kits->image);

        $kit = Product::query()->where('slug', 'kit-hade-recargada')->sole();
        $this->assertTrue($kit->category->is($kits));
        $this->assertSame(['76.90', '90.00'], [$kit->price, $kit->compare_at_price]);
        $this->assertSame('<p>Incluye</p><ul><li>Lámpara recargable</li><li>Top Coat</li></ul>', $kit->description);
        $this->assertCount(2, $kit->images);
        $this->assertTrue($kit->is_in_stock);
        Storage::disk('public')->assertExists($kit->images);

        $lamp = Product::query()->where('slug', 'lampara-conectada')->sole();
        $this->assertSame('18.00', $lamp->price);
        $this->assertNull($lamp->compare_at_price);
        $this->assertFalse($lamp->is_in_stock);

        $settings = StoreSetting::current();
        Storage::disk('public')->assertExists([$settings->logo, $settings->header_image]);
    }

    public function test_it_only_fills_what_existing_products_are_missing(): void
    {
        $this->fakeTiendada(self::STORE_URL);
        $kit = Product::factory()->create([
            'name' => 'Kit Hade Recargada',
            'slug' => 'kit-hade-recargada',
            'price' => 70.00,
            'description' => '<p>Escrita por la dueña</p>',
            'images' => [],
        ]);

        $this->artisan('hade:import-tiendada', ['--url' => self::STORE_URL, '--store' => 'S-1'])
            ->expectsOutputToContain('Productos nuevos: 1. Productos completados: 1.')
            ->assertSuccessful();

        $kit->refresh();
        $this->assertSame('70.00', $kit->price);
        $this->assertSame('<p>Escrita por la dueña</p>', $kit->description);
        $this->assertCount(2, $kit->images);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_a_download_that_is_not_an_image_is_skipped_and_reported(): void
    {
        $this->fakeTiendada(self::STORE_URL, brokenImage: true);

        $this->artisan('hade:import-tiendada', ['--url' => self::STORE_URL, '--store' => 'S-1'])
            ->expectsOutputToContain('No se pudo descargar la imagen '.self::STORE_URL.'/api/scrooge/file/FL-BROKEN?v=-2.')
            ->assertSuccessful();

        $this->assertCount(1, Product::query()->where('slug', 'kit-hade-recargada')->sole()->images);
    }
}
