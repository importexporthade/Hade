<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoredImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_photos_removed_from_a_product_are_deleted_and_the_rest_kept(): void
    {
        $kept = UploadedFile::fake()->image('a.jpg')->store('products', 'public');
        $removed = UploadedFile::fake()->image('b.jpg')->store('products', 'public');
        $product = Product::factory()->create(['images' => [$kept, $removed]]);

        $product->update(['images' => [$kept]]);

        Storage::disk('public')->assertExists($kept);
        Storage::disk('public')->assertMissing($removed);
    }

    public function test_deleting_a_product_deletes_its_photos(): void
    {
        $photo = UploadedFile::fake()->image('a.jpg')->store('products', 'public');
        $product = Product::factory()->create(['images' => [$photo]]);

        $product->delete();

        Storage::disk('public')->assertMissing($photo);
    }

    public function test_replacing_a_banner_image_deletes_the_old_one(): void
    {
        $old = UploadedFile::fake()->image('old.jpg')->store('banners', 'public');
        $new = UploadedFile::fake()->image('new.jpg')->store('banners', 'public');
        $banner = Banner::factory()->create(['image' => $old]);

        $banner->update(['image' => $new]);

        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }
}
