<?php

namespace App\Filament\Resources\FrequentQuestions\Pages;

use App\Filament\Resources\FrequentQuestions\FrequentQuestionResource;
use App\Filament\Support\AlwaysReordering;
use App\Filament\Support\WithoutBreadcrumbs;
use App\Models\StoreSetting;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageFrequentQuestions extends ManageRecords
{
    use AlwaysReordering, WithoutBreadcrumbs;

    protected static string $resource = FrequentQuestionResource::class;

    protected static ?string $title = 'Preguntas frecuentes';

    protected function getHeaderActions(): array
    {
        return [
            $this->toggleSectionAction(),
            CreateAction::make(),
        ];
    }

    /**
     * Turns the whole section on or off in the store, keeping every question as it is. Its
     * texts are read when the button is drawn, so they follow the switch right away.
     */
    private function toggleSectionAction(): Action
    {
        $isShown = fn (): bool => StoreSetting::current()->shows_frequent_questions;

        return Action::make('toggleSection')
            ->label(fn (): string => $isShown() ? 'Apagar sección' : 'Prender sección')
            ->icon(fn (): Heroicon => $isShown() ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
            ->color(fn (): string => $isShown() ? 'gray' : 'success')
            ->requiresConfirmation()
            ->modalHeading(fn (): string => $isShown() ? '¿Apagar las preguntas frecuentes?' : '¿Prender las preguntas frecuentes?')
            ->modalDescription(fn (): string => $isShown()
                ? 'Se esconden de la portada, del menú y de su página. Tus preguntas se guardan tal cual.'
                : 'Las preguntas visibles vuelven a la portada, al menú y a su página.')
            ->modalSubmitActionLabel(fn (): string => $isShown() ? 'Apagar' : 'Prender')
            ->action(function () use ($isShown): void {
                $turnsOn = ! $isShown();

                StoreSetting::current()->update(['shows_frequent_questions' => $turnsOn]);

                Notification::make()
                    ->title($turnsOn ? 'Sección prendida' : 'Sección apagada')
                    ->success()
                    ->send();
            });
    }
}
