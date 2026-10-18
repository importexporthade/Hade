<?php

namespace Tests\Feature;

use App\Enums\ProductEventType;
use App\Filament\Widgets\OrdersChart;
use App\Filament\Widgets\ProductViewsChart;
use App\Filament\Widgets\PurchaseFunnelChart;
use App\Models\Checkout;
use App\Models\ProductEvent;
use App\Models\User;
use App\Support\StoreMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MetricsChartsTest extends TestCase
{
    use RefreshDatabase;

    private const string ANA = 'a0000000-0000-4000-8000-000000000001';

    private const string BEA = 'a0000000-0000-4000-8000-000000000002';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-17 15:00:00');
    }

    public function test_every_day_of_the_period_is_listed_and_quiet_days_count_as_zero(): void
    {
        ProductEvent::factory()->count(2)->create(['created_at' => '2026-10-17 09:00:00']);
        ProductEvent::factory()->create(['created_at' => '2026-10-15 20:00:00']);
        ProductEvent::factory()->create(['created_at' => '2026-10-09 10:00:00']);

        $this->assertSame(
            ['2026-10-11' => 0, '2026-10-12' => 0, '2026-10-13' => 0, '2026-10-14' => 0, '2026-10-15' => 1, '2026-10-16' => 0, '2026-10-17' => 2],
            StoreMetrics::dailyEvents(ProductEventType::View, 7),
        );
    }

    public function test_orders_are_counted_per_day(): void
    {
        Checkout::factory()->count(3)->create(['created_at' => '2026-10-16 12:00:00']);

        $orders = StoreMetrics::dailyCheckouts(7);

        $this->assertSame(3, $orders['2026-10-16']);
        $this->assertSame(0, $orders['2026-10-17']);
    }

    public function test_the_funnel_counts_each_visitor_once_per_step(): void
    {
        ProductEvent::factory()->count(5)->create(['visitor_id' => self::ANA]);
        ProductEvent::factory()->create(['visitor_id' => self::BEA]);
        ProductEvent::factory()->ofType(ProductEventType::AddToCart)->count(2)->create(['visitor_id' => self::ANA]);
        Checkout::factory()->create(['visitor_id' => self::ANA]);

        $this->assertSame(['viewed' => 2, 'added' => 1, 'ordered' => 1], StoreMetrics::funnel(30));
    }

    public function test_an_unknown_period_falls_back_to_thirty_days(): void
    {
        $this->assertSame(7, StoreMetrics::days('7'));
        $this->assertSame(30, StoreMetrics::days('999'));
        $this->assertSame(30, StoreMetrics::days(null));
    }

    public function test_the_charts_show_on_the_dashboard_with_their_figures(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        ProductEvent::factory()->count(4)->create(['visitor_id' => self::ANA, 'created_at' => '2026-10-17 10:00:00']);
        ProductEvent::factory()->ofType(ProductEventType::AddToCart)->create(['visitor_id' => self::ANA]);
        Checkout::factory()->create(['visitor_id' => self::ANA, 'created_at' => '2026-10-17 11:00:00']);

        $this->get('/manager')->assertOk()
            ->assertSeeLivewire(ProductViewsChart::class)
            ->assertSeeLivewire(OrdersChart::class)
            ->assertSeeLivewire(PurchaseFunnelChart::class);

        Livewire::test(ProductViewsChart::class)
            ->assertSee('Visitas a productos por día')
            ->assertSee('2026-10-17: 4');

        Livewire::test(OrdersChart::class)
            ->set('filter', '7')
            ->assertSee('2026-10-17: 1')
            ->assertDontSee('2026-10-10');

        Livewire::test(PurchaseFunnelChart::class)
            ->assertSee('Vieron productos: 1')
            ->assertSee('Agregaron al carrito: 1 (100 %)')
            ->assertSee('Enviaron pedido: 1 (100 %)');
    }
}
