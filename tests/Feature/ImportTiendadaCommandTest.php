<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTiendadaCommandTest extends TestCase
{
    use RefreshDatabase;

    private const string STORE_URL = 'https://tienda.example';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_it_copies_products_categories_photos_logo_and_header(): void
    {
        $this->fakeTiendada();

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
        $this->fakeTiendada();
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
        $this->fakeTiendada(brokenImage: true);

        $this->artisan('hade:import-tiendada', ['--url' => self::STORE_URL, '--store' => 'S-1'])
            ->expectsOutputToContain('No se pudo descargar la imagen '.self::STORE_URL.'/api/scrooge/file/FL-BROKEN?v=-2.')
            ->assertSuccessful();

        $this->assertCount(1, Product::query()->where('slug', 'kit-hade-recargada')->sole()->images);
    }

    private function fakeTiendada(bool $brokenImage = false): void
    {
        $png = UploadedFile::fake()->image('photo.png', 8, 8)->get();
        $base = self::STORE_URL;

        Http::fake([
            "{$base}/api/winehouse/product*" => Http::response([
                'data' => [
                    ['id' => 'PR-KIT', 'name' => 'Kit Hade Recargada', 'price' => 90],
                    ['id' => 'PR-LAMP', 'name' => 'Lámpara Conectada', 'price' => 18],
                ],
                'pagination' => ['page' => 0, 'size' => 50, 'total' => 2],
            ]),
            "{$base}/api/im/category/product*" => Http::response([
                'data' => [
                    ['id' => 'PC-KITS', 'name' => 'Kits y Promociones', 'portraitImageId' => 'FL-CAT1'],
                    ['id' => 'PC-LAMPS', 'name' => 'Lámparas', 'portraitImageId' => null],
                ],
            ]),
            "{$base}/api/scrooge/file/FL-BROKEN*" => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'text/html']),
            "{$base}/api/scrooge/file/*" => Http::response($png, 200, ['Content-Type' => 'image/png']),
            "{$base}/product/PR-KIT" => Http::response($this->productPage(
                name: 'Kit Hade Recargada',
                images: ['FL-K1', $brokenImage ? 'FL-BROKEN' : 'FL-K2'],
                description: 'Incluye\\n\\n- Lámpara recargable\\n- Top Coat',
                price: '76.90',
                availability: 'InStock',
                categoryId: 'PC-KITS',
                categoryName: 'Kits y Promociones',
            )),
            "{$base}/product/PR-LAMP" => Http::response($this->productPage(
                name: 'Lámpara Conectada',
                images: ['FL-L1'],
                description: '',
                price: '18.00',
                availability: 'OutOfStock',
                categoryId: 'PC-LAMPS',
                categoryName: 'Lámparas',
            )),
            "{$base}/" => Http::response(
                '<html><head><title>Hade</title></head><body>'
                .'<img class="style-module__headerPoster" src="/api/scrooge/file/FL-HEADER?v=-2" alt="">'
                .'<img src="/api/scrooge/file/FL-LOGO?v=-2" alt="Hade"/></body></html>'
            ),
        ]);
    }

    /**
     * @param  list<string>  $images
     */
    private function productPage(string $name, array $images, string $description, string $price, string $availability, string $categoryId, string $categoryName): string
    {
        $product = json_encode([
            '@type' => 'Product',
            'name' => $name,
            'image' => array_map(fn (string $id): string => self::STORE_URL."/api/scrooge/file/{$id}?v=-2", $images),
            'description' => $description,
            'offers' => ['@type' => 'Offer', 'price' => $price, 'availability' => "https://schema.org/{$availability}"],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $breadcrumb = json_encode([
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $categoryName, 'item' => self::STORE_URL."/category/{$categoryId}"],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $name],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return "<html><script type=\"application/ld+json\">{$product}</script><script type=\"application/ld+json\">{$breadcrumb}</script></html>";
    }
}
