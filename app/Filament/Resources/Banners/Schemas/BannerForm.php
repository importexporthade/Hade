<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Imágenes')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('image')
                            ->label('Imagen para computadora')
                            ->helperText('Horizontal, idealmente 1920 × 640 px.')
                            ->required()
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public'),
                        FileUpload::make('mobile_image')
                            ->label('Imagen para celular (opcional)')
                            ->helperText('Vertical o cuadrada, idealmente 1080 × 1080 px. Si no la subes se usa la de computadora.')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('banners')
                            ->visibility('public'),
                    ]),
                Section::make('Texto y enlace')
                    ->description('Todo opcional: un banner puede ser solo la imagen.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->maxLength(255),
                        TextInput::make('subtitle')
                            ->label('Subtítulo')
                            ->maxLength(255),
                        TextInput::make('button_label')
                            ->label('Texto del botón')
                            ->placeholder('Ver ofertas')
                            ->maxLength(60),
                        TextInput::make('link_url')
                            ->label('Enlace al hacer clic')
                            ->placeholder('/category/kits-y-promociones')
                            ->maxLength(255),
                    ]),
                Section::make('Publicación')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                        DateTimePicker::make('starts_at')
                            ->label('Mostrar desde')
                            ->helperText('Déjalo vacío para mostrarlo ya.')
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Mostrar hasta')
                            ->helperText('Déjalo vacío para que no venza.')
                            ->seconds(false)
                            ->after('starts_at'),
                    ]),
            ]);
    }
}
