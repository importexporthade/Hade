<?php

namespace Tests\Feature;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlugInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_the_link_drops_accents_and_symbols_from_the_name(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Lámpara Ñandú & Glitter!'])
            ->assertSchemaStateSet(['slug' => 'lampara-nandu-glitter']);
    }

    public function test_the_link_tooltip_shows_the_address_the_name_will_have(): void
    {
        Livewire::test(CreateProduct::class)
            ->assertSee('product/kit-hade-recargada')
            ->fillForm(['name' => 'Lámpara Ñandú'])
            ->assertSee('product/lampara-nandu')
            ->assertDontSee('product/kit-hade-recargada');
    }

    public function test_a_typed_link_turns_spaces_into_dashes_and_loses_special_characters(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Top Coat',
                'slug' => '  Top Coát_Brillo  ¿2026?',
                'category_id' => Category::factory()->create()->id,
                'price' => '12.00',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('top-coat-brillo-2026', Product::query()->sole()->slug);
    }

    public function test_a_link_written_by_the_owner_is_kept_when_the_name_changes(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Top Coat'])
            ->fillForm(['slug' => 'brillo-final'])
            ->fillForm(['name' => 'Top Coat Gel'])
            ->assertSchemaStateSet(['slug' => 'brillo-final']);
    }

    public function test_the_link_keeps_following_the_name_until_the_owner_edits_it(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Top'])
            ->fillForm(['name' => 'Top Coat'])
            ->assertSchemaStateSet(['slug' => 'top-coat']);
    }

    public function test_a_link_made_only_of_symbols_is_rejected(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Top Coat',
                'slug' => '¡¿%$#?!',
                'category_id' => Category::factory()->create()->id,
                'price' => '12.00',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'required']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_a_new_category_gets_its_link_from_the_name(): void
    {
        Livewire::test(ManageCategories::class)
            ->callAction(TestAction::make(CreateAction::class), data: ['name' => 'Lámparas UV'])
            ->assertHasNoFormErrors();

        $this->assertSame('lamparas-uv', Category::query()->sole()->slug);
    }

    public function test_a_new_information_page_gets_its_link_from_the_title(): void
    {
        Livewire::test(CreatePage::class)
            ->fillForm(['title' => 'Envíos y delivery'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('envios-y-delivery', Page::query()->sole()->slug);
    }
}
