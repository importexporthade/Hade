<?php

namespace Tests\Feature;

use App\Enums\BannerLinkType;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BannerLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_banner_leads_to_the_product_category_or_page_chosen(): void
    {
        $product = Product::factory()->create(['slug' => 'kit-hade-recargada']);
        $category = Category::factory()->create(['slug' => 'lamparas']);
        Product::factory()->for($category)->create();
        $page = Page::factory()->create(['slug' => 'envios-y-delivery']);

        $this->assertSame(route('products.show', $product), Banner::factory()->create(['link_type' => BannerLinkType::Product, 'link_product_id' => $product->id])->linkUrl());
        $this->assertSame(route('categories.show', $category), Banner::factory()->create(['link_type' => BannerLinkType::Category, 'link_category_id' => $category->id])->linkUrl());
        $this->assertSame(route('pages.show', $page), Banner::factory()->create(['link_type' => BannerLinkType::Page, 'link_page_id' => $page->id])->linkUrl());
        $this->assertSame(route('products.index'), Banner::factory()->create(['link_type' => BannerLinkType::AllProducts])->linkUrl());
        $this->assertNull(Banner::factory()->create(['link_type' => BannerLinkType::None])->linkUrl());
    }

    public function test_a_banner_can_open_the_store_whatsapp_or_another_site(): void
    {
        StoreSetting::current()->update(['whatsapp_number' => '51 986 000 362', 'whatsapp_message' => null]);

        $this->assertSame('https://wa.me/51986000362', Banner::factory()->create(['link_type' => BannerLinkType::WhatsApp])->linkUrl());
        $this->assertSame('https://www.instagram.com/hade.pe', Banner::factory()->create(['link_type' => BannerLinkType::External, 'link_url' => 'https://www.instagram.com/hade.pe'])->linkUrl());
    }

    public function test_a_banner_whose_destination_was_hidden_or_deleted_leads_nowhere(): void
    {
        $hiddenProduct = Product::factory()->hidden()->create();
        $deletedProduct = Product::factory()->create();
        $toHiddenProduct = Banner::factory()->create(['link_type' => BannerLinkType::Product, 'link_product_id' => $hiddenProduct->id]);
        $toDeletedProduct = Banner::factory()->create(['link_type' => BannerLinkType::Product, 'link_product_id' => $deletedProduct->id]);

        $deletedProduct->delete();

        $this->assertNull($toHiddenProduct->linkUrl());
        $this->assertNull($toDeletedProduct->refresh()->linkUrl());
        $this->assertSame('Producto: eliminado', $toDeletedProduct->destinationLabel());
    }

    public function test_changing_the_destination_forgets_the_previous_one(): void
    {
        $product = Product::factory()->create();
        $banner = Banner::factory()->create(['link_type' => BannerLinkType::Product, 'link_product_id' => $product->id]);

        $banner->update(['link_type' => BannerLinkType::Category, 'link_category_id' => Category::factory()->create()->id]);

        $this->assertNull($banner->refresh()->link_product_id);
        $this->assertNotNull($banner->link_category_id);
    }

    public function test_the_owner_points_a_banner_at_a_product_from_a_list(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['name' => 'Kit Hade Recargada']);

        Livewire::test(CreateBanner::class)
            ->fillForm([
                'image' => [UploadedFile::fake()->image('promo.jpg', 1200, 400)],
                'link_type' => BannerLinkType::Product->value,
                'link_product_id' => $product->id,
                'button_label' => 'Ver kit',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(BannerResource::getUrl('index'));

        $banner = Banner::query()->sole();
        $this->assertSame(BannerLinkType::Product, $banner->link_type);
        $this->assertTrue($banner->linkProduct->is($product));
    }

    public function test_choosing_a_product_destination_requires_picking_the_product(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm([
                'image' => [UploadedFile::fake()->image('promo.jpg', 1200, 400)],
                'link_type' => BannerLinkType::Product->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['link_product_id' => 'required']);
    }

    public function test_another_site_needs_a_full_address(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateBanner::class)
            ->fillForm([
                'image' => [UploadedFile::fake()->image('promo.jpg', 1200, 400)],
                'link_type' => BannerLinkType::External->value,
                'link_url' => 'instagram hade',
            ])
            ->call('create')
            ->assertHasFormErrors(['link_url' => 'url']);
    }

    public function test_the_home_page_links_the_banner_and_opens_outside_sites_in_a_new_tab(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['slug' => 'kit-hade-recargada']);
        Banner::factory()->create(['link_type' => BannerLinkType::Product, 'link_product_id' => $product->id]);
        Banner::factory()->create(['link_type' => BannerLinkType::External, 'link_url' => 'https://www.tiktok.com/@hade.pe']);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('products.show', $product).'"', false)
            ->assertSee('href="https://www.tiktok.com/@hade.pe"', false)
            ->assertSee('target="_blank"', false);
    }
}
