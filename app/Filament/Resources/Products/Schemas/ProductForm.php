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
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El nombre con el que la clienta ve el producto: en su tarjeta del catálogo, en su página y en el carrito. De él sale también el enlace.')
                                ->required()
                                ->maxLength(255)
                                ->live(debounce: 500)
                                ->afterStateUpdated(SlugInput::fillFromName()),
                            SlugInput::make('products.show', 'kit-hade-recargada'),
                            Select::make('category_id')
                                ->label('Categoría')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La sección de la tienda donde aparece el producto, por ejemplo Lámparas: la clienta lo encuentra al entrar a esa categoría. Con el botón + creas una categoría nueva sin salir de aquí.')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->createOptionForm(fn (Schema $schema) => CategoryResource::form($schema)),
                            TextInput::make('sku')
                                ->label('Código (SKU)')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Un código interno para que tú reconozcas el producto, por ejemplo el de tu proveedor o tu inventario. Es opcional, no se muestra en la tienda y no puede repetirse.')
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            RichEditor::make('description')
                                ->label('Descripción')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El texto que lee la clienta en la página del producto: qué incluye, medidas, cómo se usa. Puedes poner negritas, títulos y listas. Su comienzo también se ve al compartir el enlace por WhatsApp.')
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
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Las fotos de la galería en la página del producto; la primera es la principal. Hasta 10 fotos de máximo 10 MB cada una. Se optimizan solas para que la tienda cargue rápido.')
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
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La foto cuadrada de la tarjeta del producto en el catálogo, la portada y las búsquedas. Es opcional: si no la subes, se usa la primera foto de la galería.')
                                ->imageCropAspectRatio('1:1')
                                ->imageEditorAspectRatios(['1:1'])
                                ->helperText('Se recorta cuadrada. Usa el lápiz para elegir qué parte se ve.'),
                        ]),
                    Section::make('Precio')
                        ->schema([
                            TextInput::make('price')
                                ->label('Precio de venta')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo que paga la clienta, en soles. Es el precio que se ve en la tarjeta, en la página del producto y en el carrito.')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('S/')
                                ->live(onBlur: true),
                            TextInput::make('compare_at_price')
                                ->label('Precio normal (tachado)')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El precio de antes, que se ve tachado junto al precio de venta para mostrar la oferta y su porcentaje. Debe ser mayor que el precio de venta. Llénalo y el producto entra a la sección de Ofertas; déjalo vacío si no está en oferta.')
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
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Encendido, el producto aparece en la tienda. Apagado, queda guardado aquí pero nadie lo ve: útil mientras lo preparas o si dejaste de venderlo.')
                                ->default(true)
                                ->live(),
                            Callout::make('Su categoría está oculta')
                                ->key('hiddenCategoryNotice')
                                ->description(fn (Get $get): string => 'La categoría «'.self::hiddenCategory($get('category_id'))?->name.'» no se ve en la tienda, así que este producto tampoco aparecerá hasta que la muestres.')
                                ->warning()
                                ->visible(fn (Get $get): bool => $get('is_visible') && self::hiddenCategory($get('category_id')) !== null)
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
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Apágalo cuando se agote: el producto sigue en la tienda con la etiqueta «Agotado» y no se puede agregar al carrito. Enciéndelo cuando vuelva a llegar.')
                                ->default(true),
                            Toggle::make('is_featured')
                                ->label('Novedad')
                                ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo muestra en la sección «Novedades» de la portada y en la página Novedades del menú, del más nuevo al más antiguo. Los productos nuevos empiezan encendidos: apágalo cuando el producto ya no sea una novedad.')
                                ->helperText('Aparece en Novedades, en la portada y en el menú.')
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
