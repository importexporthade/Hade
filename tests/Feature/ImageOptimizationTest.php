<?php

namespace Tests\Feature;

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

    public function test_a_large_photo_is_stored_as_webp_within_the_size_with_its_thumbnail(): void
    {
        $contents = UploadedFile::fake()->image('big.png', 3000, 2000)->get();

        $path = ImageOptimizer::store($contents, 'products', 1200, 480);

        $this->assertStringStartsWith('products/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([1200, 800, 'image/webp'], $this->dimensions($path));
        $this->assertSame([480, 320, 'image/webp'], $this->dimensions('products/thumbs/'.basename($path)));
    }

    public function test_a_small_photo_keeps_its_size(): void
    {
        $path = ImageOptimizer::store(UploadedFile::fake()->image('small.jpg', 300, 200)->get(), 'categories', 800);

        $this->assertSame([300, 200, 'image/webp'], $this->dimensions($path));
        Storage::disk('public')->assertMissing('categories/thumbs/'.basename($path));
    }

    public function test_contents_that_are_not_an_image_are_not_stored(): void
    {
        $this->assertNull(ImageOptimizer::store('<html>no soy una foto</html>', 'products', 1200));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_photos_uploaded_in_the_manager_are_stored_optimized(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Kit Hade Recargada',
                'category_id' => Category::factory()->create()->id,
                'price' => '76.90',
                'images' => [UploadedFile::fake()->image('foto-del-celular.jpg', 2400, 1800)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $path = Product::query()->sole()->images[0];
        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([1200, 900, 'image/webp'], $this->dimensions($path));
        Storage::disk('public')->assertExists(ImageOptimizer::thumbnailPath($path));
    }

    public function test_the_command_converts_old_photos_and_removes_the_originals(): void
    {
        Storage::disk('public')->put('products/old.png', UploadedFile::fake()->image('old.png', 2000, 2000)->get());
        $alreadyOptimized = ImageOptimizer::store(UploadedFile::fake()->image('new.png', 500, 500)->get(), 'products', 1200, 480);
        $product = Product::factory()->create(['images' => ['products/old.png', $alreadyOptimized]]);

        $this->artisan('hade:optimize-images')
            ->expectsOutputToContain('Fotos optimizadas: 1.')
            ->assertSuccessful();

        [$converted, $kept] = $product->refresh()->images;
        $this->assertStringEndsWith('.webp', $converted);
        $this->assertSame([1200, 1200, 'image/webp'], $this->dimensions($converted));
        $this->assertSame($alreadyOptimized, $kept);
        Storage::disk('public')->assertMissing('products/old.png');
        Storage::disk('public')->assertExists(ImageOptimizer::thumbnailPath($converted));
    }

    public function test_product_tiles_use_the_thumbnail_and_old_photos_fall_back_to_the_full_one(): void
    {
        $optimized = Product::factory()->create(['images' => ['products/abc.webp']]);
        $legacy = Product::factory()->create(['images' => ['products/abc.png']]);

        $this->assertStringEndsWith('/storage/products/thumbs/abc.webp', $optimized->coverThumbnailUrl());
        $this->assertStringEndsWith('/storage/products/abc.webp', $optimized->coverImageUrl());
        $this->assertStringEndsWith('/storage/products/abc.png', $legacy->coverThumbnailUrl());
    }

    public function test_deleting_a_product_also_deletes_the_thumbnails(): void
    {
        $path = ImageOptimizer::store(UploadedFile::fake()->image('a.png', 900, 900)->get(), 'products', 1200, 480);
        $product = Product::factory()->create(['images' => [$path]]);

        $product->delete();

        Storage::disk('public')->assertMissing([$path, ImageOptimizer::thumbnailPath($path)]);
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
