<?php

namespace Tests\Feature;

use App\Enums\ProductEventType;
use App\Filament\Widgets\ProductInterest;
use App\Filament\Widgets\StoreActivity;
use App\Http\Middleware\IdentifyVisitor;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ProductEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductMetricsTest extends TestCase
{
    use RefreshDatabase;

    private const string BROWSER = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withHeader('User-Agent', self::BROWSER);
    }

    public function test_opening_a_product_records_a_view_with_an_anonymous_visitor(): void
    {
        $product = Product::factory()->create(['slug' => 'kit-hade-recargada', 'price' => 76.90]);

        $this->get('/product/kit-hade-recargada')
            ->assertOk()
            ->assertCookie(IdentifyVisitor::COOKIE);

        $event = ProductEvent::query()->sole();
        $this->assertTrue($event->product->is($product));
        $this->assertSame(ProductEventType::View, $event->type);
        $this->assertSame('76.90', $event->unit_price);
        $this->assertNotNull($event->visitor_id);
    }

    public function test_link_previews_robots_and_the_signed_in_owner_are_not_counted(): void
    {
        Product::factory()->create(['slug' => 'top-coat']);

        $this->withHeader('User-Agent', 'WhatsApp/2.24.1 A')->get('/product/top-coat')->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')->get('/product/top-coat')->assertOk();
        $this->withHeader('User-Agent', self::BROWSER)->actingAs(User::factory()->admin()->create())->get('/product/top-coat')->assertOk();

        $this->assertDatabaseCount('product_events', 0);
    }

    public function test_the_cart_reports_products_added_and_removed(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/metrics/cart', ['product_id' => $product->id, 'change' => 'add', 'quantity' => 2])->assertNoContent();
        $this->postJson('/metrics/cart', ['product_id' => $product->id, 'change' => 'remove', 'quantity' => 1])->assertNoContent();

        $this->assertSame(
            [[ProductEventType::AddToCart, 2], [ProductEventType::RemoveFromCart, 1]],
            ProductEvent::query()->orderBy('id')->get()->map(fn (ProductEvent $event): array => [$event->type, $event->quantity])->all(),
        );
    }

    public function test_hidden_or_unknown_products_are_not_recorded_from_the_cart(): void
    {
        $hidden = Product::factory()->hidden()->create();

        $this->postJson('/metrics/cart', ['product_id' => $hidden->id, 'change' => 'add', 'quantity' => 1])->assertNoContent();
        $this->postJson('/metrics/cart', ['product_id' => 999999, 'change' => 'add', 'quantity' => 1])->assertNoContent();
        $this->postJson('/metrics/cart', ['product_id' => $hidden->id, 'change' => 'steal', 'quantity' => 1])->assertUnprocessable();

        $this->assertDatabaseCount('product_events', 0);
    }

    public function test_a_whatsapp_order_is_recorded_with_catalog_prices_not_the_browser_ones(): void
    {
        $kit = Product::factory()->create(['price' => 76.90]);
        $topCoat = Product::factory()->create(['price' => 12.00]);

        $this->postJson('/metrics/checkout', ['items' => [
            ['id' => $kit->id, 'quantity' => 2, 'price' => 0.01],
            ['id' => $topCoat->id, 'quantity' => 1],
        ]])->assertNoContent();

        $checkout = Checkout::query()->sole();
        $this->assertSame(3, $checkout->items_count);
        $this->assertSame('165.80', $checkout->total);
        $this->assertSame(
            [[$kit->id, 2, '76.90'], [$topCoat->id, 1, '12.00']],
            $checkout->events()->orderBy('id')->get()->map(fn (ProductEvent $event): array => [$event->product_id, $event->quantity, $event->unit_price])->all(),
        );
    }

    public function test_the_dashboard_sums_up_the_last_30_days(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['name' => 'Kit Hade Recargada']);
        ProductEvent::factory()->for($product)->count(3)->create(['visitor_id' => 'a0000000-0000-4000-8000-000000000001']);
        ProductEvent::factory()->for($product)->create(['visitor_id' => 'a0000000-0000-4000-8000-000000000002']);
        ProductEvent::factory()->for($product)->ofType(ProductEventType::AddToCart)->create(['quantity' => 2]);
        Checkout::factory()->create(['visitor_id' => 'a0000000-0000-4000-8000-000000000001', 'total' => 153.80]);
        $this->travel(-40)->days();
        ProductEvent::factory()->for($product)->create();
        $this->travelBack();

        Livewire::test(StoreActivity::class)
            ->assertSeeInOrder(['Productos vistos', '4', '2 visitantes', 'Agregados al carrito', '2', 'Pedidos por WhatsApp', '1', 'S/ 153.80', '% de ida a WhatsApp', '50 %']);

        Livewire::test(ProductInterest::class)
            ->assertCanSeeTableRecords([$product])
            ->assertSee('Kit Hade Recargada');
    }
}
