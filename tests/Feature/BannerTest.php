<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_banners_inside_their_dates_are_showing(): void
    {
        $this->freezeTime();

        $always = Banner::factory()->create();
        $running = Banner::factory()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
        Banner::factory()->inactive()->create();
        Banner::factory()->create(['starts_at' => now()->addMinute()]);
        Banner::factory()->create(['ends_at' => now()]);

        $this->assertEqualsCanonicalizing([$always->id, $running->id], Banner::query()->showing()->pluck('id')->all());
    }
}
