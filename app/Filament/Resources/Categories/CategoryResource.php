<?php

namespace App\Filament\Resources\Categories;

use App\Enums\ImageKind;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Support\PhotoUpload;
use App\Filament\Support\SlugInput;
use App\Models\Category;
use App\Support\ImageOptimizer;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Así se llamará en el menú y en la portada. Ej: Lámparas.')
                    ->autofocus()
                    ->required()
                    ->maxLength(255)
                    ->live(debounce: 500)
                    ->afterStateUpdated(SlugInput::fillFromName()),
                SlugInput::make('categories.show', 'lamparas'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Una frase que sale bajo el nombre, en la página de la categoría. Ej: Secado rápido para tus diseños.')
                    ->rows(3)
                    ->columnSpanFull(),
                PhotoUpload::make('image', ImageKind::Category)
                    ->label('Foto')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La foto que la representa en «Universo Hade», en la portada.')
                    ->columnSpanFull(),
                Toggle::make('is_visible')
                    ->label('Visible en la tienda')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Apágala para esconderla aunque tenga productos. Si no tiene ninguno visible, se esconde sola.')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withVisibleProductsFlag())
            ->defaultSort('sort_order')
            ->description('Una categoría sin productos visibles se oculta sola y vuelve cuando muestras uno. El interruptor Visible la oculta a mano y se guarda al tocarlo.')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Foto')
                    ->state(fn (Category $record): ?string => $record->image ? ImageOptimizer::displayPath($record->image, 320) : null)
                    ->disk('public')
                    ->square(),
                TextColumn::make('name')
                    ->label('Categoría')
                    ->description(fn (Category $record): ?string => $record->hasVisibleProducts() ? null : 'Oculta: no tiene productos visibles')
                    ->searchable(),
                TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products')
                    ->badge(),
                ToggleColumn::make('is_visible')
                    ->label('Visible'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Sus productos se quedarán sin categoría, no se borran.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
