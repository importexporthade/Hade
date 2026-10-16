<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Support\Favicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_the_tab_icon_is_a_small_square_cut_from_the_logo(): void
    {
        $this->uploadLogo('logo.png', 600, 400);

        $url = Favicon::url(32);

        $this->assertStringEndsWith('-32.png', $url);
        $info = getimagesizefromstring(Storage::disk('public')->get(str_replace('/storage/', '', $url)));
        $this->assertSame([32, 32, 'image/png'], [$info[0], $info[1], $info['mime']]);
    }

    public function test_without_a_logo_the_tab_shows_the_monogram(): void
    {
        $this->assertSame(asset('images/favicon.svg'), Favicon::url(32));
    }

    public function test_a_new_logo_replaces_the_old_icons(): void
    {
        $this->uploadLogo('old.png', 300, 300);
        $oldIcon = str_replace('/storage/', '', Favicon::url(32));

        $this->uploadLogo('new.png', 300, 300);
        $newIcon = str_replace('/storage/', '', Favicon::url(32));

        $this->assertNotSame($oldIcon, $newIcon);
        Storage::disk('public')->assertMissing($oldIcon);
        Storage::disk('public')->assertExists($newIcon);
    }

    public function test_the_store_and_the_manager_show_the_icon(): void
    {
        $this->withoutVite();
        $this->uploadLogo('logo.png', 300, 300);
        $icon = Favicon::url(32);

        $this->get('/products')->assertOk()->assertSee('<link rel="icon" href="'.$icon.'"', false);
        $this->get('/manager/login')->assertOk()->assertSee($icon, false);
    }

    private function uploadLogo(string $name, int $width, int $height): void
    {
        $path = UploadedFile::fake()->image($name, $width, $height)->store('store', 'public');

        StoreSetting::current()->update(['logo' => $path]);
    }
}
