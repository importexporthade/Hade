<?php

namespace App\Filament\Resources\HomeSections;

use App\Enums\HomeSectionType;
use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Models\HomeSection;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'Tienda online';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Secciones de portada';

    protected static ?string $modelLabel = 'sección';

    protected static ?string $pluralModelLabel = 'secciones de portada';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->placeholder('Stickers Manicure')
                    ->required()
                    ->maxLength(255),
                TextInput::make('subtitle')
                    ->label('Subtítulo')
                    ->maxLength(255),
                Select::make('type')
                    ->label('Qué muestra')
                    ->options(HomeSectionType::class)
                    ->required()
                    ->live(),
                Select::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => self::typeOf($get) === HomeSectionType::Category)
                    ->required(fn (Get $get): bool => self::typeOf($get) === HomeSectionType::Category),
                TextInput::make('products_limit')
                    ->label('Cuántos productos')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(24)
                    ->default(8)
                    ->required()
                    ->visible(fn (Get $get): bool => self::typeOf($get)?->showsProducts() ?? true),
                Toggle::make('is_active')
                    ->label('Visible en la portada')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('category'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->description('Arrastra las filas para cambiar el orden en que aparecen en la portada.')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->description(fn (HomeSection $record): ?string => $record->subtitle),
                TextColumn::make('type')
                    ->label('Qué muestra')
                    ->badge()
                    ->description(fn (HomeSection $record): ?string => $record->category?->name),
                TextColumn::make('products_limit')
                    ->label('Productos')
                    ->state(fn (HomeSection $record): ?int => $record->type->showsProducts() ? $record->products_limit : null)
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label('Visible'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHomeSections::route('/'),
        ];
    }

    private static function typeOf(Get $get): ?HomeSectionType
    {
        $type = $get('type');

        return $type instanceof HomeSectionType ? $type : HomeSectionType::tryFrom((string) $type);
    }
}
