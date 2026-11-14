<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageStore;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_saves_the_store_details(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageStore::class)
            ->fillForm([
                'store_name' => 'Hade',
                'whatsapp_number' => '51 986 000 362',
                'whatsapp_message' => 'Hola, quiero comprar',
                'instagram_url' => 'https://www.instagram.com/hade.pe',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = StoreSetting::query()->sole();
        $this->assertSame('51 986 000 362', $settings->whatsapp_number);
        $this->assertSame('https://www.instagram.com/hade.pe', $settings->instagram_url);
        $this->assertSame('https://wa.me/51986000362?text=Hola%2C%20quiero%20comprar', $settings->whatsappUrl());
    }

    public function test_the_owner_writes_the_shipping_and_delivery_in_one_text(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageStore::class)
            ->fillForm([
                'store_name' => 'Hade',
                'shipping' => "Lima: delivery S/ 10, llega en 24 a 48 horas.\nProvincia: por Shalom desde S/ 15.",
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame("Lima: delivery S/ 10, llega en 24 a 48 horas.\nProvincia: por Shalom desde S/ 15.", StoreSetting::query()->sole()->shipping);
    }

    public function test_the_form_opens_with_the_saved_details(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        StoreSetting::current()->update(['announcement' => 'Delivery a todo el Perú']);

        Livewire::test(ManageStore::class)
            ->assertSchemaStateSet(['announcement' => 'Delivery a todo el Perú']);
    }

    public function test_social_links_must_be_web_addresses(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageStore::class)
            ->fillForm(['store_name' => 'Hade', 'facebook_url' => 'hade.pe'])
            ->call('save')
            ->assertHasFormErrors(['facebook_url' => 'url']);
    }

    public function test_there_is_no_whatsapp_link_without_a_number(): void
    {
        $this->assertNull(StoreSetting::current()->whatsappUrl());
    }
}
