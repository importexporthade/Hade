<?php

namespace App\Filament\Resources\Categories;

use App\Enums\ImageKind;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Support\PhotoUpload;
use App\Models\Category;
use App\Support\ImageOptimizer;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
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
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),
                PhotoUpload::make('image', ImageKind::Category)
                    ->label('Foto')
                    ->columnSpanFull(),
                Toggle::make('is_visible')
                    ->label('Visible en la tienda')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->description('El interruptor Visible se guarda solo al tocarlo. Para cambiar nombre o foto, pulsa Editar.')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('Foto')
                    ->state(fn (Category $record): ?string => $record->image ? ImageOptimizer::displayPath($record->image, 320) : null)
                    ->disk('public')
                    ->square(),
                TextColumn::make('name')
                    ->label('Categoría')
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
