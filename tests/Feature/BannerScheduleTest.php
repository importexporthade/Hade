<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Support\DateTimeField;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BannerScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_a_day_and_an_hour_are_saved_as_the_banner_window(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm([
                'image' => [UploadedFile::fake()->image('promo.jpg', 1200, 400)],
                'starts_at_date' => '2026-10-20',
                'starts_at_time' => '09:00',
                'ends_at_date' => '2026-10-31',
            ])
            ->assertSchemaStateSet(['ends_at_time' => DateTimeField::END_OF_DAY])
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::query()->sole();
        $this->assertSame('2026-10-20 09:00:00', $banner->starts_at->toDateTimeString());
        $this->assertSame('2026-10-31 23:59:00', $banner->ends_at->toDateTimeString());
    }

    public function test_without_dates_the_banner_shows_from_now_and_never_expires(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm(['image' => [UploadedFile::fake()->image('promo.jpg', 1200, 400)]])
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::query()->sole();
        $this->assertNull($banner->starts_at);
        $this->assertNull($banner->ends_at);
    }

    public function test_the_end_must_come_after_the_start(): void
    {
        Livewire::test(CreateBanner::class)
            ->fillForm([
                'image' => [UploadedFile::fake()->image('promo.jpg', 1200, 400)],
                'starts_at_date' => '2026-10-20',
                'starts_at_time' => '18:00',
                'ends_at_date' => '2026-10-20',
                'ends_at_time' => '09:00',
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at_date'])
            ->assertSee('La fecha de fin debe ser posterior a la de inicio.');

        $this->assertDatabaseCount('banners', 0);
    }

    public function test_editing_opens_with_the_saved_day_and_hour_split(): void
    {
        $banner = Banner::factory()->create(['starts_at' => '2026-12-01 20:30:00', 'ends_at' => null]);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->assertSchemaStateSet([
                'starts_at_date' => '2026-12-01',
                'starts_at_time' => '20:30',
                'ends_at_date' => null,
            ])
            ->assertSee('Se verá desde el 01/12/2026, 8:30 p. m., sin fecha de fin.');
    }

    public function test_the_summary_reads_the_window_in_plain_words(): void
    {
        $banner = Banner::factory()->create(['starts_at' => '2026-10-15 14:00:00', 'ends_at' => '2026-10-17 23:00:00']);

        Livewire::test(EditBanner::class, ['record' => $banner->getRouteKey()])
            ->assertSee('Se verá del 15/10/2026, 2:00 p. m. hasta el 17/10/2026, 11:00 p. m.')
            ->assertDontSee('p. m..');
    }

    public function test_hours_are_offered_every_half_hour_in_twelve_hour_time(): void
    {
        $options = DateTimeField::timeOptions();

        $this->assertSame('12:00 a. m.', $options['00:00']);
        $this->assertSame('9:30 a. m.', $options['09:30']);
        $this->assertSame('12:00 p. m.', $options['12:00']);
        $this->assertSame('11:59 p. m. (fin del día)', $options['23:59']);
        $this->assertCount(49, $options);
        $this->assertSame('2:15 p. m.', DateTimeField::timeOptions('14:15')['14:15']);
    }
}
