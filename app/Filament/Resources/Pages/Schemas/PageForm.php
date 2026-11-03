<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageVisibility;
use App\Filament\Support\SlugInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                            ->placeholder('Envíos y delivery')
                            ->required()
                            ->maxLength(255)
                            ->live(debounce: 500)
                            ->afterStateUpdated(SlugInput::fillFromName()),
                        SlugInput::make('hadetienda.com/information/envios-y-delivery'),
                        RichEditor::make('content')
                            ->label('Contenido'),
                    ]),
                Section::make('Dónde se ve')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Radio::make('visibility')
                            ->hiddenLabel()
                            ->options(PageVisibility::class)
                            ->default(PageVisibility::MenuAndFooter->value)
                            ->required(),
                    ]),
            ]);
    }
}
