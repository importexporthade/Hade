<?php

namespace App\Filament\Resources\Complaints\Schemas;

use App\Models\Complaint;
use App\Support\Price;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ComplaintInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Hoja de reclamación')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('sequence')
                            ->label('N°')
                            ->formatStateUsing(fn (Complaint $record): string => $record->code()),
                        TextEntry::make('created_at')
                            ->label('Registrada')
                            ->dateTime(),
                        TextEntry::make('type')
                            ->label('Tipo')
                            ->badge(),
                        TextEntry::make('due')
                            ->label('Responder hasta')
                            ->state(fn (Complaint $record): string => $record->isAnswered()
                                ? 'Respondida el '.$record->responded_at->format('d/m/Y')
                                : $record->responseDueDate()->format('d/m/Y'))
                            ->color(fn (Complaint $record): ?string => ! $record->isAnswered() && $record->responseDueDate()->isPast() ? 'danger' : null),
                    ]),
                Section::make('Clienta')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('consumer_name')->label('Nombre'),
                        TextEntry::make('document_number')
                            ->label('Documento')
                            ->formatStateUsing(fn (Complaint $record): string => "{$record->document_type->getLabel()} {$record->document_number}"),
                        TextEntry::make('address')->label('Domicilio')->placeholder('No indicado'),
                        TextEntry::make('phone')->label('Teléfono')->placeholder('No indicado'),
                        TextEntry::make('email')->label('Correo')->copyable(),
                        TextEntry::make('guardian_name')
                            ->label('Menor de edad, representada por')
                            ->visible(fn (Complaint $record): bool => $record->is_minor),
                    ]),
                Section::make('Detalle')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextEntry::make('item_description')
                            ->label('Producto o servicio')
                            ->formatStateUsing(fn (Complaint $record): string => "{$record->item_type->getLabel()}: {$record->item_description}"),
                        TextEntry::make('amount')
                            ->label('Monto reclamado')
                            ->formatStateUsing(fn (?string $state): ?string => Price::format($state))
                            ->placeholder('No indicado'),
                        TextEntry::make('detail')->label('Qué pasó'),
                        TextEntry::make('consumer_request')->label('Qué solicita'),
                    ]),
                Section::make('Respuesta de la tienda')
                    ->columnSpanFull()
                    ->visible(fn (Complaint $record): bool => $record->isAnswered())
                    ->schema([
                        TextEntry::make('response')->hiddenLabel(),
                    ]),
            ]);
    }
}
