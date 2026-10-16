<?php

namespace App\Providers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Pages\BasePage;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->keepSaveButtonsInView();
        $this->confirmInlineChanges();
    }

    /**
     * Switches in the lists (Visible, Stock, Destacado…) save the moment they are tapped,
     * with no button; a notice says so, or the owner keeps looking for a save button.
     */
    private function confirmInlineChanges(): void
    {
        ToggleColumn::configureUsing(fn (ToggleColumn $column): ToggleColumn => $column->afterStateUpdated(
            fn (bool $state) => Notification::make()
                ->success()
                ->title('Cambio guardado')
                ->body($column->getLabel().': '.($state ? 'sí' : 'no'))
                ->send(),
        ));
    }

    /**
     * Product and category forms are long, and their save buttons sat below the fold where
     * the owner did not find them; they now stay pinned at the bottom of the screen.
     */
    private function keepSaveButtonsInView(): void
    {
        BasePage::stickyFormActions();

        CreateAction::configureUsing(fn (CreateAction $action): CreateAction => $action->stickyModalFooter());
        EditAction::configureUsing(fn (EditAction $action): EditAction => $action->stickyModalFooter());
    }
}
