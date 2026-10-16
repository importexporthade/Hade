<?php

namespace Tests\Feature;

use App\Enums\ImageKind;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_a_photo_is_stored_as_webp_with_a_copy_for_every_width_of_its_kind(): void
    {
        $path = ImageOptimizer::store(UploadedFile::fake()->image('big.png', 1800, 1200)->get(), ImageKind::Product);

        $this->assertStringStartsWith('products/', $path);
        $this->assertSame([1200, 800, 'image/webp'], $this->dimensions($path));
        $this->assertSame([320, 213], array_slice($this->dimensions(ImageOptimizer::variantPath($path, 320)), 0, 2));
        $this->assertSame([480, 320], array_slice($this->dimensions(ImageOptimizer::variantPath($path, 480)), 0, 2));
        $this->assertSame([800, 533], array_slice($this->dimensions(ImageOptimizer::variantPath($path, 800)), 0, 2));
    }

    public function test_copies_of_a_small_photo_keep_its_size_instead_of_enlarging_it(): void
    {
        $path = ImageOptimizer::store(UploadedFile::fake()->image('small.jpg', 300, 200)->get(), ImageKind::Category);

        $this->assertSame([300, 200], array_slice($this->dimensions($path), 0, 2));
        $this->assertSame([300, 200], array_slice($this->dimensions(ImageOptimizer::variantPath($path, 480)), 0, 2));
    }

    public function test_contents_that_are_not_an_image_are_not_stored(): void
    {
        $this->assertNull(ImageOptimizer::store('<html>no soy una foto</html>', ImageKind::Product));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_srcset_lists_every_copy_with_its_width(): void
    {
        $this->assertSame(
            '/storage/categories/abc-320.webp 320w, /storage/categories/abc-480.webp 480w, /storage/categories/abc.webp 800w',
            ImageOptimizer::srcset('categories/abc.webp', ImageKind::Category),
        );
        $this->assertNull(ImageOptimizer::srcset('categories/old.png', ImageKind::Category));
    }

    public function test_photos_uploaded_in_the_manager_are_stored_with_their_copies(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Kit Hade Recargada',
                'category_id' => Category::factory()->create()->id,
                'price' => '76.90',
                'images' => [UploadedFile::fake()->image('foto-del-celular.jpg', 1600, 1200)],
                'cover_image' => [UploadedFile::fake()->image('portada.jpg', 900, 900)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->sole();
        $this->assertSame([1200, 900, 'image/webp'], $this->dimensions($product->images[0]));
        Storage::disk('public')->assertExists(ImageOptimizer::variantPath($product->images[0], 480));
        $this->assertSame([800, 800, 'image/webp'], $this->dimensions($product->cover_image));
        Storage::disk('public')->assertExists(ImageOptimizer::variantPath($product->cover_image, 320));
    }

    public function test_the_tile_uses_the_cropped_cover_or_else_the_first_gallery_photo(): void
    {
        $withCover = Product::factory()->create(['images' => ['products/a.webp'], 'cover_image' => 'products/cover.webp']);
        $withoutCover = Product::factory()->create(['images' => ['products/a.webp', 'products/b.webp']]);
        $withoutPhotos = Product::factory()->create(['images' => []]);

        $this->assertSame('/storage/products/cover-480.webp', $withCover->coverUrl(480));
        $this->assertSame(ImageKind::ProductCover, $withCover->coverKind());
        $this->assertSame('/storage/products/a-480.webp', $withoutCover->coverUrl(480));
        $this->assertStringEndsWith('images/product-placeholder.svg', $withoutPhotos->coverUrl(480));
    }

    public function test_the_command_converts_old_photos_and_completes_missing_copies(): void
    {
        $disk = Storage::disk('public');
        $disk->put('products/old.png', UploadedFile::fake()->image('old.png', 1500, 1500)->get());
        $disk->put('products/legacy.webp', UploadedFile::fake()->image('legacy.png', 900, 900)->get());
        $disk->put('products/thumbs/legacy.webp', 'copia del sistema anterior');
        $product = Product::factory()->create(['images' => ['products/old.png', 'products/legacy.webp']]);

        $this->artisan('hade:optimize-images')
            ->expectsOutputToContain('Fotos convertidas a WebP: 1. Fotos con copias nuevas: 1.')
            ->assertSuccessful();

        [$converted, $legacy] = $product->refresh()->images;
        $this->assertStringEndsWith('.webp', $converted);
        $this->assertSame('products/legacy.webp', $legacy);
        $disk->assertMissing(['products/old.png', 'products/thumbs/legacy.webp']);
        $disk->assertExists([ImageOptimizer::variantPath($converted, 800), ImageOptimizer::variantPath($legacy, 320)]);
    }

    public function test_deleting_a_product_also_deletes_every_copy(): void
    {
        $path = ImageOptimizer::store(UploadedFile::fake()->image('a.png', 900, 900)->get(), ImageKind::Product);
        $product = Product::factory()->create(['images' => [$path]]);

        $product->delete();

        Storage::disk('public')->assertMissing([$path, ...array_map(fn (int $width): string => ImageOptimizer::variantPath($path, $width), ImageKind::Product->widths())]);
    }

    public function test_the_store_offers_the_browser_every_size_of_a_tile_photo(): void
    {
        $this->withoutVite();
        Product::factory()->create(['name' => 'Kit Hade Recargada', 'images' => ['products/kit.webp']]);

        $this->get('/products')
            ->assertOk()
            ->assertSee('src="/storage/products/kit-480.webp"', false)
            ->assertSee('/storage/products/kit-320.webp 320w', false);
    }

    /**
     * @return array{0: int, 1: int, 2: string}
     */
    private function dimensions(string $path): array
    {
        $info = getimagesizefromstring(Storage::disk('public')->get($path));

        return [$info[0], $info[1], $info['mime']];
    }
}
