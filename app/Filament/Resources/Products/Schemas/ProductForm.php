<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ImageKind;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\PhotoUpload;
use App\Filament\Support\SlugInput;
use App\Models\Category;
use App\Support\Price;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Información')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->label('Nombre')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Así lo verán tus clientas en la tienda y en su pedido por WhatsApp. Ej: Kit Hade Recargada.')
                                // Also brings the cursor back here after "Crear y seguir aquí"
                                ->autofocus(fn (string $operation): bool => $operation === 'create')
                                ->required()
                                ->maxLength(255)
                                ->live(debounce: 500)
                                ->afterStateUpdated(SlugInput::fillFromName()),
                            SlugInput::make('products.show', 'kit-hade-recargada'),
                            Select::make('category_id')
                                ->label('Categoría')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La parte de la tienda donde lo van a encontrar. Ej: Lámparas.')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->createOptionForm(fn (Schema $schema) => CategoryResource::form($schema))
                                ->createOptionModalHeading('Nueva categoría')
                                ->columnSpanFull(),
                            RichEditor::make('description')
                                ->label('Descripción')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Cuéntale a tu clienta lo que necesita saber antes de comprar: qué trae, cuánto dura, cómo se usa. Ej: Trae 20 stickers y duran 2 semanas.')
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'link'],
                                    ['h2', 'h3', 'bulletList', 'orderedList'],
                                    ['undo', 'redo'],
                                ])
                                ->columnSpanFull(),
                        ]),
                    Section::make('Fotos')
                        ->description('Se ven en la página del producto. Arrástralas para cambiar el orden.')
                        ->schema([
                            PhotoUpload::make('images', ImageKind::Product)
                                ->hiddenLabel()
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Puedes subir hasta 10. La primera es la que se ve primero; arrástralas para cambiar el orden.')
                                ->multiple()
                                ->reorderable()
                                ->appendFiles()
                                ->panelLayout('grid')
                                ->maxFiles(10),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Portada')
                        ->description('La foto de la tarjeta en la tienda. Si no subes una, se usa la primera foto de la galería.')
                        ->schema([
                            PhotoUpload::make('cover_image', ImageKind::ProductCover)
                                ->hiddenLabel()
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La foto de la tarjeta en el catálogo, lo primero que ven tus clientas. Si no subes una, usamos la primera de la galería.')
                                ->imageCropAspectRatio('1:1')
                                ->imageEditorAspectRatios(['1:1'])
                                ->helperText('Se recorta cuadrada. Usa el lápiz para elegir qué parte se ve.'),
                        ]),
                    Section::make('Precio')
                        ->schema([
                            TextInput::make('price')
                                ->label('Precio de venta')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo que paga tu clienta, en soles. Ej: 35.00.')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('S/')
                                ->live(onBlur: true),
                            TextInput::make('compare_at_price')
                                ->label('Precio normal (tachado)')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El precio de antes. Si lo llenas, sale tachado junto al precio actual y el producto pasa a Ofertas. Ej: 45.00.')
                                ->helperText(fn (Get $get): string => self::discountHint($get('price'), $get('compare_at_price')))
                                ->numeric()
                                ->prefix('S/')
                                ->gt('price')
                                ->validationMessages(['gt' => 'El precio normal debe ser mayor que el precio de venta.'])
                                ->live(onBlur: true),
                        ]),
                    Section::make('Estado')
                        ->schema([
                            Toggle::make('is_visible')
                                ->label('Visible en la tienda')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Apágalo para esconder el producto un tiempo sin borrarlo. Lo vuelves a prender cuando quieras.')
                                ->default(true)
                                ->live(),
                            Callout::make('Su categoría está oculta')
                                ->key('hiddenCategoryNotice')
                                ->description(fn (Get $get): string => 'La categoría «'.self::hiddenCategory($get('category_id'))?->name.'» no se ve en la tienda, así que este producto tampoco aparecerá hasta que la muestres.')
                                ->warning()
                                // A "Novedad" shows even inside a hidden category, so there is nothing to warn about
                                ->visible(fn (Get $get): bool => $get('is_visible') && ! $get('is_featured') && self::hiddenCategory($get('category_id')) !== null)
                                ->actions([
                                    Action::make('showCategory')
                                        ->label('Mostrar la categoría')
                                        ->requiresConfirmation()
                                        ->modalHeading('¿Mostrar la categoría en la tienda?')
                                        ->modalDescription('También aparecerán los demás productos visibles de esta categoría.')
                                        ->modalSubmitActionLabel('Sí, mostrarla')
                                        ->action(function (Get $get): void {
                                            self::hiddenCategory($get('category_id'))?->update(['is_visible' => true]);

                                            Notification::make()->title('La categoría ya se ve en la tienda')->success()->send();
                                        }),
                                ]),
                            Toggle::make('is_in_stock')
                                ->label('Hay stock')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Apágalo cuando se te acabe: saldrá «Agotado» y nadie podrá pedirlo hasta que lo vuelvas a prender.')
                                ->default(true),
                            Toggle::make('is_featured')
                                ->label('Novedad')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Préndelo para que salga en Novedades y se luzca. Cuando ya no sea nuevo, lo apagas.')
                                ->helperText('Aparece en Novedades, en la portada y en el menú, aunque su categoría esté oculta.')
                                ->default(true)
                                ->live(),
                        ]),
                    Section::make('Envíos y delivery')
                        ->description('Dónde se puede enviar este producto. Cada uno muestra su texto de Datos de la tienda.')
                        ->schema([
                            Toggle::make('ships_to_capital')
                                ->label('Lima')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Muestra en la página del producto tu texto de «Envíos a capital» de Datos de la tienda. Apágalo si este producto no se envía dentro de Lima.')
                                ->default(true),
                            Toggle::make('ships_to_province')
                                ->label('Provincia')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Muestra en la página del producto tu texto de «Envíos a provincia» de Datos de la tienda. Apágalo si este producto no viaja fuera de Lima. Ej: algo frágil.')
                                ->default(true),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /**
     * The chosen category when the owner hid it, since its products stay out of the store
     * even while they are switched on.
     */
    private static function hiddenCategory(mixed $categoryId): ?Category
    {
        return filled($categoryId) ? Category::query()->whereKey($categoryId)->where('is_visible', false)->first() : null;
    }

    private static function discountHint(mixed $price, mixed $compareAtPrice): string
    {
        if (! is_numeric($price) || ! is_numeric($compareAtPrice) || (float) $compareAtPrice <= (float) $price) {
            return 'Llénalo solo si el producto está en oferta.';
        }

        $discount = (int) round((1 - $price / $compareAtPrice) * 100);

        return "Oferta: -{$discount} %, la clienta ahorra ".Price::format($compareAtPrice - $price).'.';
    }
}
