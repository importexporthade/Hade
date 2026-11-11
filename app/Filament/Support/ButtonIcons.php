<?php

namespace App\Filament\Support;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Every button in the manager carries an icon. Filament gives one to some of its buttons
 * ("Editar", "Borrar") and none to others ("Guardar", "Cancelar", "Crear"…), so a
 * window could show both kinds side by side. Registered once, this fills in the icon of
 * every button that has none; a button that sets its own icon keeps it.
 */
class ButtonIcons
{
    /**
     * Icons for buttons without one, by the button's name (Filament's own and Hade's).
     *
     * @var array<string, Heroicon>
     */
    private const array BY_NAME = [
        // Create and edit pages, and the store settings
        'create' => Heroicon::OutlinedCheck,
        'createAnother' => Heroicon::OutlinedPlus,
        'save' => Heroicon::OutlinedCheck,
        'cancel' => Heroicon::OutlinedXMark,
        'back' => Heroicon::OutlinedArrowLeft,
        // Signing in and unlocking the account
        'authenticate' => Heroicon::OutlinedArrowRightEndOnRectangle,
        'unlock' => Heroicon::OutlinedLockOpen,
        'login' => Heroicon::OutlinedArrowLeft,
        // Table filters and columns
        'applyFilters' => Heroicon::OutlinedCheck,
        'resetFilters' => Heroicon::OutlinedArrowPath,
        'removeAllFilters' => Heroicon::OutlinedXMark,
        'applyTableColumnManager' => Heroicon::OutlinedCheck,
        'resetColumnManager' => Heroicon::OutlinedArrowPath,
        // Hade's own buttons
        'showCategory' => Heroicon::OutlinedEye,
        'categories' => Heroicon::OutlinedArrowRight,
    ];

    public static function register(): void
    {
        Action::configureUsing(function (Action $button): void {
            // Worked out when the button is drawn, after Filament has set up its own defaults;
            // any ->icon() set later on the button replaces it
            $button->icon(fn (): string|BackedEnum|Htmlable|null => self::forButton($button));

            // The confirm button of the window this button opens ("Guardar", "Borrar");
            // Filament hands it over as $action, and a button that sets its own
            // ->modalSubmitAction() replaces this
            $button->modalSubmitAction(fn (Action $action): Action => $action->icon(self::forConfirmButtonOf($button)));
        });
    }

    /**
     * Filament's "Editar", "Borrar" and "Ver" only show their icon inside a table; the same icon
     * now goes with them everywhere else, such as "Borrar" at the top of an edit page.
     */
    private static function forButton(Action $action): string|BackedEnum|Htmlable|null
    {
        if ($action instanceof CreateAction) {
            return Heroicon::OutlinedPlus;
        }

        return self::BY_NAME[$action->getName()] ?? $action->getTableIcon();
    }

    /**
     * Deleting confirms with the bin, anything else with a tick. A window whose confirm says
     * something else ("Descargar", "Enviar respuesta") sets its own icon on the spot.
     */
    private static function forConfirmButtonOf(Action $opener): Heroicon
    {
        return $opener instanceof DeleteAction || $opener instanceof DeleteBulkAction || $opener->getColor() === 'danger'
            ? Heroicon::OutlinedTrash
            : Heroicon::OutlinedCheck;
    }
}
