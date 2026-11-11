<?php

namespace App\Providers;

use App\Filament\Support\ButtonIcons;
use App\Filament\Support\ManagerLoginResponse;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Pages\BasePage;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * How dates and times read across the manager: day first and a 12-hour clock.
     */
    public const string DATE_FORMAT = 'd/m/Y';

    public const string TIME_FORMAT = 'h:i a';

    public const string DATE_TIME_FORMAT = 'd/m/Y h:i a';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Signing in never lands on one of Livewire's own addresses
        $this->app->bind(LoginResponse::class, ManagerLoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->keepSaveButtonsInView();
        $this->showValidationInsideTheForm();
        $this->useTwelveHourClock();
        $this->stackTablesOnMobile();
        $this->closeFiltersAfterApplying();
        ButtonIcons::register();
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

    /**
     * Filament marks required fields for the browser, which then stops the form with its own
     * grey bubble ("Completa este campo"). Turning that off lets the Spanish messages appear
     * under each field, styled like the rest of the manager.
     */
    private function showValidationInsideTheForm(): void
    {
        Form::configureUsing(fn (Form $form): Form => $form->extraAttributes(['novalidate' => true], merge: true));

        // Windows (create a category, answer a complaint…) are a form of their own
        Action::configureUsing(fn (Action $action): Action => $action->extraModalWindowAttributes(['novalidate' => true], merge: true));
    }

    private function useTwelveHourClock(): void
    {
        Table::configureUsing(fn (Table $table): Table => $table
            ->defaultDateDisplayFormat(self::DATE_FORMAT)
            ->defaultTimeDisplayFormat(self::TIME_FORMAT)
            ->defaultDateTimeDisplayFormat(self::DATE_TIME_FORMAT));

        Schema::configureUsing(fn (Schema $schema): Schema => $schema
            ->defaultDateDisplayFormat(self::DATE_FORMAT)
            ->defaultTimeDisplayFormat(self::TIME_FORMAT)
            ->defaultDateTimeDisplayFormat(self::DATE_TIME_FORMAT));

        DateTimePicker::configureUsing(fn (DateTimePicker $picker): DateTimePicker => $picker
            ->native(false)
            ->displayFormat(self::DATE_TIME_FORMAT));

        // A date picker is a date-time picker underneath, so it would otherwise show "12:00 am"
        DatePicker::configureUsing(fn (DatePicker $picker): DatePicker => $picker
            ->displayFormat(self::DATE_FORMAT));
    }

    /**
     * On a phone the owner had to scroll every table sideways; each row now reads as a card
     * with the column name above its value, and desktop keeps the usual table.
     */
    private function stackTablesOnMobile(): void
    {
        Table::configureUsing(fn (Table $table): Table => $table->stackedOnMobile());
    }

    /**
     * The filters panel stayed open over the list after "Aplicar filtros", so the owner had
     * to close it to see the result; applying now closes it.
     */
    private function closeFiltersAfterApplying(): void
    {
        Table::configureUsing(fn (Table $table): Table => $table->filtersApplyAction(
            fn (Action $action): Action => $action->close(),
        ));
    }
}
