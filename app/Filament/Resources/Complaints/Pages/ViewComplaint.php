<?php

namespace App\Filament\Resources\Complaints\Pages;

use App\Filament\Resources\Complaints\ComplaintResource;
use App\Mail\ComplaintAnswered;
use App\Models\Complaint;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * @property-read Complaint $record
 */
class ViewComplaint extends ViewRecord
{
    protected static string $resource = ComplaintResource::class;

    public function getTitle(): string|Htmlable
    {
        return "Hoja N° {$this->record->code()}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('respond')
                ->label('Responder')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn (): bool => ! $this->record->isAnswered())
                ->modalHeading('Responder la hoja de reclamación')
                ->modalDescription(fn (): string => "La respuesta se envía por correo a {$this->record->email} y queda guardada en el libro. No se puede cambiar después.")
                ->modalSubmitActionLabel('Enviar respuesta')
                ->schema([
                    Textarea::make('response')
                        ->label('Respuesta')
                        ->helperText('Explica qué hará la tienda: cambio, devolución, disculpa u otra solución.')
                        ->required()
                        ->minLength(20)
                        ->maxLength(3000)
                        ->rows(8),
                ])
                ->action(function (array $data): void {
                    $this->record->respond($data['response']);

                    try {
                        Mail::to($this->record->email)->send(new ComplaintAnswered($this->record));
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->warning()
                            ->title('Respuesta guardada, pero el correo no salió')
                            ->body("Avisa a la clienta por otro medio: {$this->record->phone}.")
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Respuesta enviada')
                        ->send();
                }),
        ];
    }
}
