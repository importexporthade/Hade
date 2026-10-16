<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\BannerLinkType;
use App\Enums\ImageKind;
use App\Filament\Support\PhotoUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                        PhotoUpload::make('image', ImageKind::Banner)
                            ->label('Imagen para computadora')
                            ->helperText('Horizontal, idealmente 1920 × 640 px.')
                            ->required(),
                        PhotoUpload::make('mobile_image', ImageKind::MobileBanner)
                            ->label('Imagen para celular (opcional)')
                            ->helperText('Vertical o cuadrada, idealmente 1080 × 1080 px. Si no la subes se usa la de computadora.'),
                    ]),
                Section::make('Texto')
                    ->description('Opcional: un banner puede ser solo la imagen.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->maxLength(255),
                        TextInput::make('subtitle')
                            ->label('Subtítulo')
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
                Section::make('Al tocar el banner')
                    ->description('Elige a dónde lleva a la clienta cuando toca el banner.')
                    ->columnSpanFull()
                    ->schema([
                        Radio::make('link_type')
                            ->hiddenLabel()
                            ->options(BannerLinkType::class)
                            ->default(BannerLinkType::None->value)
                            ->required()
                            ->live()
                            ->columns(['md' => 2]),
                        Select::make('link_product_id')
                            ->label('¿Qué producto?')
                            ->relationship('linkProduct', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Escribe para buscar, por ejemplo "Kit Hade"')
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Product)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Product),
                        Select::make('link_category_id')
                            ->label('¿Qué categoría?')
                            ->relationship('linkCategory', 'name')
                            ->preload()
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Category)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Category),
                        Select::make('link_page_id')
                            ->label('¿Qué página?')
                            ->relationship('linkPage', 'title')
                            ->preload()
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Page)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Page),
                        TextInput::make('link_url')
                            ->label('Dirección de la página')
                            ->helperText('Ábrela en tu navegador, copia lo que aparece arriba en la barra y pégalo aquí.')
                            ->placeholder('https://www.instagram.com/hade.pe')
                            ->url()
                            ->validationMessages(['url' => 'Pega la dirección completa, empezando por https://'])
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::External)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::External),
                        TextInput::make('button_label')
                            ->label('Texto del botón (opcional)')
                            ->helperText('Si lo llenas, aparece un botón sobre la imagen.')
                            ->placeholder('Ver ofertas')
                            ->maxLength(60)
                            ->visible(fn (Get $get): bool => (self::linkType($get) ?? BannerLinkType::None) !== BannerLinkType::None),
                    ]),
            ]);
    }

    private static function linkType(Get $get): ?BannerLinkType
    {
        $type = $get('link_type');

        return $type instanceof BannerLinkType ? $type : BannerLinkType::tryFrom((string) $type);
    }
}
