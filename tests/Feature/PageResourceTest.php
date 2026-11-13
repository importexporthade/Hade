<?php

namespace Tests\Feature;

use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_a_page_goes_back_to_the_list_of_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $page = Page::factory()->create(['title' => 'Envíos y delivery']);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm(['title' => 'Envíos a todo el Perú'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(PageResource::getUrl('index'));

        $this->assertSame('Envíos a todo el Perú', $page->fresh()->title);
    }
}
