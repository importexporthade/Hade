<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanServerCommandTest extends TestCase
{
    use RefreshDatabase;

    private const array PHOTOS_IN_USE = [
        'products/kit.webp', 'products/kit-320.webp', 'products/kit-480.webp',
        'products/cover.webp', 'categories/manicure.webp', 'banners/promo.webp',
        'banners/promo-movil.webp', 'store/logo.webp', 'products/legacy.png',
    ];

    private const array UNUSED_PHOTOS = [
        'products/failed-upload.webp', 'products/failed-upload-320.webp', 'categories/replaced.webp',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->travelTo(now());

        foreach ([...self::PHOTOS_IN_USE, ...self::UNUSED_PHOTOS] as $path) {
            Storage::disk('public')->put($path, 'foto');
        }

        Storage::disk('public')->put('content-image.png', 'imagen pegada en una página');

        Product::factory()->create(['images' => ['products/kit.webp', 'products/legacy.png'], 'cover_image' => 'products/cover.webp']);
        Category::factory()->create(['image' => 'categories/manicure.webp']);
        Banner::factory()->create(['image' => 'banners/promo.webp', 'mobile_image' => 'banners/promo-movil.webp']);
        StoreSetting::current()->update(['logo' => 'store/logo.webp']);

        $this->travel(2)->hours();
    }

    public function test_without_borrar_it_only_lists_the_unused_photos(): void
    {
        $this->artisan('hade:clean-server')
            ->expectsOutputToContain('Fotos sin usar: 3')
            ->expectsOutputToContain('products/failed-upload.webp')
            ->expectsOutputToContain('No se borró nada')
            ->assertSuccessful();

        Storage::disk('public')->assertExists([...self::PHOTOS_IN_USE, ...self::UNUSED_PHOTOS]);
    }

    public function test_with_borrar_it_deletes_only_photos_nothing_uses(): void
    {
        $this->artisan('hade:clean-server --borrar')->assertSuccessful();

        Storage::disk('public')->assertMissing(self::UNUSED_PHOTOS);
        Storage::disk('public')->assertExists([...self::PHOTOS_IN_USE, 'content-image.png']);
    }

    public function test_a_photo_uploaded_moments_ago_is_kept_while_its_product_is_being_saved(): void
    {
        // Files carry the real clock's time, so the command runs at the real time here
        $this->travelBack();
        Storage::disk('public')->put('products/saving-now.webp', 'foto');

        $this->artisan('hade:clean-server --borrar')->assertSuccessful();

        Storage::disk('public')->assertExists('products/saving-now.webp');
    }

    public function test_with_borrar_it_empties_the_error_log(): void
    {
        $log = storage_path('logs/clean-server-test.log');
        File::put($log, str_repeat('production.ERROR algo falló', 100));

        try {
            $this->artisan('hade:clean-server --borrar')->assertSuccessful();

            $this->assertSame('', File::get($log));
        } finally {
            File::delete($log);
        }
    }
}
