<?php

namespace App\Filament\Resources\Banners\Schemas;

use App\Enums\BannerLinkType;
use App\Enums\ImageKind;
use App\Filament\Support\DateTimeField;
use App\Filament\Support\PhotoUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La foto grande del carrusel al inicio de la portada, tal como se ve en computadoras. Va horizontal; se optimiza sola para que cargue rápido.')
                            ->helperText('Horizontal, idealmente 1920 × 640 px.')
                            ->required(),
                        PhotoUpload::make('mobile_image', ImageKind::MobileBanner)
                            ->label('Imagen para celular (opcional)')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Otra versión de la foto para celulares, donde la pantalla es angosta y alta. Si no la subes, el celular muestra la de computadora, que puede verse recortada.')
                            ->helperText('Vertical o cuadrada, idealmente 1080 × 1080 px. Si no la subes se usa la de computadora.'),
                    ]),
                Section::make('Texto')
                    ->description('Opcional: un banner puede ser solo la imagen.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El texto grande que aparece encima de la foto del banner. Es opcional: déjalo vacío si la foto ya trae su propio texto.')
                            ->maxLength(255),
                        TextInput::make('subtitle')
                            ->label('Subtítulo')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Una frase más pequeña debajo del título del banner. Es opcional.')
                            ->maxLength(255),
                    ]),
                Section::make('Publicación')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Apagado, el banner deja de verse en la portada aunque esté dentro de sus fechas. No se borra.')
                            ->default(true),
                        DateTimeField::make('starts_at', 'Mostrar desde', DateTimeField::START_OF_DAY, placeholder: 'Desde ya', tooltip: 'El día y la hora en que el banner empieza a verse en la portada. Sirve para dejar listo un banner de campaña antes de tiempo. Si lo dejas vacío, se ve desde que lo guardas.'),
                        DateTimeField::make('ends_at', 'Mostrar hasta', DateTimeField::END_OF_DAY, placeholder: 'Sin fecha de fin', after: 'starts_at', tooltip: 'El día y la hora en que el banner se oculta solo, por ejemplo cuando termina una oferta. Si lo dejas vacío, se ve hasta que lo apagues.'),
                        Text::make(fn (Get $get): string => DateTimeField::summary($get, 'starts_at', 'ends_at'))
                            ->color('neutral'),
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
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El producto que se abre cuando la clienta toca el banner.')
                            ->relationship('linkProduct', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Escribe para buscar, por ejemplo "Kit Hade"')
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Product)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Product),
                        Select::make('link_category_id')
                            ->label('¿Qué categoría?')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La categoría que se abre cuando la clienta toca el banner.')
                            ->relationship('linkCategory', 'name')
                            ->preload()
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Category)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Category),
                        Select::make('link_page_id')
                            ->label('¿Qué página?')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La página informativa que se abre cuando la clienta toca el banner.')
                            ->relationship('linkPage', 'title')
                            ->preload()
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Page)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::Page),
                        TextInput::make('link_url')
                            ->label('Dirección de la página')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La dirección de otra página web que se abre al tocar el banner, por ejemplo tu Instagram o un video de TikTok.')
                            ->helperText('Ábrela en tu navegador, copia lo que aparece arriba en la barra y pégalo aquí.')
                            ->placeholder('https://www.instagram.com/hade.pe')
                            ->url()
                            ->validationMessages(['url' => 'Pega la dirección completa, empezando por https://'])
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => self::linkType($get) === BannerLinkType::External)
                            ->required(fn (Get $get): bool => self::linkType($get) === BannerLinkType::External),
                        TextInput::make('button_label')
                            ->label('Texto del botón (opcional)')
                            ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El texto del botón que aparece sobre la foto del banner, por ejemplo «Ver ofertas». Si lo dejas vacío, no aparece botón.')
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
