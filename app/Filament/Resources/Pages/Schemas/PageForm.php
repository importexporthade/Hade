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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El nombre de la página en el menú «Información» y en el pie de página, y su título grande al abrirla. De él sale también el enlace.')
                            ->placeholder('Envíos y delivery')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(SlugInput::fillFromName()),
                        SlugInput::make('pages.show', 'envios-y-delivery'),
                        RichEditor::make('content')
                            ->label('Contenido'),
                    ]),
                Section::make('Dónde se ve')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Radio::make('visibility')
                            ->hiddenLabel()
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'En qué partes de la tienda aparece el enlace a esta página. Aunque esté oculta, puedes seguir editándola aquí.')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Todo lo que lee la clienta al abrir la página, por ejemplo cómo son los envíos o los cambios. Puedes usar títulos, negritas, listas y enlaces.')
                            ->options(PageVisibility::class)
                            ->default(PageVisibility::MenuAndFooter->value)
                            ->required(),
                    ]),
            ]);
    }
}
