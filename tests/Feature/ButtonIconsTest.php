<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ButtonIconsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_buttons_of_a_create_page_all_carry_an_icon(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $page = Livewire::test(CreateProduct::class)->instance();

        $buttons = collect((fn (): array => $this->getFormActions())->call($page))
            ->mapWithKeys(fn (Action $action): array => [$action->getName() => $action->getIcon()]);

        $this->assertEquals([
            'create' => Heroicon::OutlinedCheck,
            'createAnother' => Heroicon::OutlinedPlus,
            'cancel' => Heroicon::OutlinedXMark,
        ], $buttons->all());
    }

    public function test_the_save_button_of_an_edit_page_simply_says_guardar(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $page = Livewire::test(EditProduct::class, ['record' => Product::factory()->create()->getRouteKey()])->instance();

        $buttons = collect((fn (): array => $this->getFormActions())->call($page))
            ->mapWithKeys(fn (Action $action): array => [$action->getName() => [$action->getLabel(), $action->getIcon()]]);

        $this->assertEquals([
            'save' => ['Guardar', Heroicon::OutlinedCheck],
            'cancel' => ['Cancelar', Heroicon::OutlinedXMark],
        ], $buttons->all());
    }

    public function test_filaments_edit_and_delete_buttons_keep_their_icon_outside_tables(): void
    {
        $this->assertSame(Heroicon::PencilSquare, EditAction::make()->getIcon());
        $this->assertSame(Heroicon::Trash, DeleteAction::make()->getIcon());
        $this->assertSame(Heroicon::OutlinedPlus, CreateAction::make()->getIcon());
    }

    public function test_a_window_confirms_deleting_with_the_bin_and_anything_else_with_a_tick(): void
    {
        $this->assertSame(Heroicon::OutlinedTrash, DeleteAction::make()->getModalSubmitAction()->getIcon());
        $this->assertSame(Heroicon::OutlinedXMark, EditAction::make()->getModalCancelAction()->getIcon());

        $edit = EditAction::make();
        $save = $edit->getModalSubmitAction();
        $this->assertSame(['submit', 'Guardar', Heroicon::OutlinedCheck], [$save->getName(), $save->getLabel(), $save->getIcon()]);
        $this->assertSame(Heroicon::PencilSquare, $edit->getIcon(), 'The button that opens the window keeps its own icon');
    }

    public function test_a_button_that_sets_its_own_icon_keeps_it(): void
    {
        $this->assertSame(Heroicon::OutlinedEnvelope, Action::make('save')->icon(Heroicon::OutlinedEnvelope)->getIcon());

        $download = Action::make('export')->modalSubmitAction(fn (Action $action): Action => $action->icon(Heroicon::OutlinedArrowDownTray));
        $this->assertSame(Heroicon::OutlinedArrowDownTray, $download->getModalSubmitAction()->getIcon());
    }
}
