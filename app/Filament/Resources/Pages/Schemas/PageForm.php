<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageVisibility;
use App\Filament\Support\SlugInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Cómo se llama la página en el menú Información. Ej: Envíos y delivery.')
                            ->placeholder('Envíos y delivery')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(SlugInput::fillFromName()),
                        SlugInput::make('pages.show', 'envios-y-delivery'),
                        RichEditor::make('content')
                            ->label('Contenido')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo que lee la clienta cuando abre la página. Ej: plazos y costos de envío.'),
                    ]),
                Section::make('Dónde se ve')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Radio::make('visibility')
                            ->hiddenLabel()
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Dónde quieres que salga el enlace a esta página. Ej: en el menú y al pie.')
                            ->options(PageVisibility::class)
                            ->default(PageVisibility::MenuAndFooter->value)
                            ->required(),
                    ]),
            ]);
    }
}
