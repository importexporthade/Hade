<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ImageKind;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Support\PhotoUpload;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

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
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug((string) $state));
                                    }
                                }),
                            TextInput::make('slug')
                                ->label('Enlace')
                                ->helperText('Parte final de la dirección del producto en la tienda.')
                                ->required()
                                ->maxLength(255)
                                ->alphaDash()
                                ->unique(ignoreRecord: true),
                            Select::make('category_id')
                                ->label('Categoría')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->createOptionForm(fn (Schema $schema) => CategoryResource::form($schema)),
                            TextInput::make('sku')
                                ->label('Código (SKU)')
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            RichEditor::make('description')
                                ->label('Descripción')
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
                                ->imageCropAspectRatio('1:1')
                                ->imageEditorAspectRatios(['1:1'])
                                ->helperText('Se recorta cuadrada. Usa el lápiz para elegir qué parte se ve.'),
                        ]),
                    Section::make('Precio')
                        ->schema([
                            TextInput::make('price')
                                ->label('Precio de venta')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('S/')
                                ->live(onBlur: true),
                            TextInput::make('compare_at_price')
                                ->label('Precio normal (tachado)')
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
                                ->default(true),
                            Toggle::make('is_featured')
                                ->label('Destacado')
                                ->helperText('Aparece en las secciones de destacados de la portada.'),
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
