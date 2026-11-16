<?php

namespace Tests\Feature;

use App\Enums\PageFooterSection;
use App\Enums\PageVisibility;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
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

    public function test_the_owner_can_create_a_footer_only_legal_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreatePage::class)
            ->fillForm([
                'title' => 'Condiciones de garantía',
                'slug' => 'condiciones-de-garantia',
                'content' => '<p>Condiciones personalizadas.</p>',
                'visibility' => PageVisibility::Footer->value,
                'footer_section' => PageFooterSection::Legal->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(PageResource::getUrl('index'));

        $this->assertDatabaseHas('pages', [
            'slug' => 'condiciones-de-garantia',
            'visibility' => 'footer',
            'footer_section' => 'legal',
        ]);
    }

    public function test_the_footer_section_selector_follows_visibility_and_preserves_the_saved_section_when_hidden(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $page = Page::factory()->create([
            'visibility' => PageVisibility::Footer,
            'footer_section' => PageFooterSection::Legal,
        ]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->assertFormFieldIsVisible('footer_section')
            ->fillForm(['visibility' => PageVisibility::MenuAndFooter->value])
            ->assertFormFieldIsVisible('footer_section')
            ->fillForm(['visibility' => PageVisibility::Hidden->value])
            ->assertFormFieldIsHidden('footer_section')
            ->fillForm(['visibility' => PageVisibility::Menu->value])
            ->assertFormFieldIsHidden('footer_section')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(PageFooterSection::Legal, $page->fresh()->footer_section);
    }

    public function test_the_owner_can_change_the_footer_column_of_an_existing_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $page = Page::factory()->create();

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm([
                'visibility' => PageVisibility::Footer->value,
                'footer_section' => PageFooterSection::Legal->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'visibility' => 'footer', 'footer_section' => 'legal']);
    }

    #[TestWith(['visibility', 'invalid'])]
    #[TestWith(['footer_section', 'invalid'])]
    #[TestWith(['footer_section', null])]
    public function test_invalid_placement_choices_are_not_saved(string $field, ?string $value): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $page = Page::factory()->create(['visibility' => PageVisibility::Footer]);

        Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
            ->fillForm([$field => $value])
            ->call('save')
            ->assertHasFormErrors([$field]);

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'visibility' => 'footer', 'footer_section' => 'information']);
    }
}
