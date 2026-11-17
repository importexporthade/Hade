<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\TiendadaImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\FakesTiendada;
use Tests\TestCase;

class TiendadaImportTest extends TestCase
{
    use FakesTiendada;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->fakeTiendada(TiendadaImport::STORE_URL);
    }

    public function test_a_batch_out_of_time_leaves_the_rest_for_the_next_one(): void
    {
        $firstBatch = TiendadaImport::fromHadeStore();
        $firstBatch->run(seconds: 0);

        $this->assertSame(['PR-KIT'], $firstBatch->handledProducts);
        $this->assertSame(1, $firstBatch->remainingProducts);
        $this->assertDatabaseCount('products', 1);
        $this->assertNull(StoreSetting::current()->logo);

        $secondBatch = TiendadaImport::fromHadeStore();
        $secondBatch->run($firstBatch->handledProducts, seconds: 0);

        $this->assertSame(['PR-LAMP'], $secondBatch->handledProducts);
        $this->assertSame(0, $secondBatch->remainingProducts);
        $this->assertDatabaseCount('products', 2);
        Storage::disk('public')->assertExists(StoreSetting::current()->logo);
    }

    public function test_products_that_already_have_photos_are_left_alone(): void
    {
        $kit = Product::factory()->create([
            'name' => 'Kit Hade Recargada',
            'slug' => 'kit-hade-recargada',
            'images' => ['products/de-la-duena.webp'],
        ]);

        $import = TiendadaImport::fromHadeStore();
        $import->run();

        $this->assertSame(['PR-LAMP'], $import->handledProducts);
        $this->assertSame(['products/de-la-duena.webp'], $kit->fresh()->images);
        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/product/PR-KIT'));
    }
}
