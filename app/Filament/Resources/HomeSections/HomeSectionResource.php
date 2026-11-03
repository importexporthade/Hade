<?php

namespace App\Filament\Resources\HomeSections;

use App\Enums\HomeSectionType;
use App\Filament\Resources\HomeSections\Pages\ManageHomeSections;
use App\Models\HomeSection;
use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Tables\Columns\ViewColumn;
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

    /**
     * On a phone the whole card is a drag handle, so a finger only picks a section up after
     * holding it for a moment; until then the list scrolls as usual.
     */
    private const string HOLD_TO_DRAG = <<<'JS'
        const list = $event.target.closest('[x-sortable]');

        if (list?.sortable) {
            list.sortable.option('delay', 300);
            list.sortable.option('delayOnTouchOnly', true);
        }
        JS;

    /**
     * A plain click on a section opens its edit window. Its own switch and buttons keep doing
     * their job, and a drag never ends in a click.
     */
    private const string CLICK_TO_EDIT = <<<'JS'
        const row = $event.target.closest('[x-sortable-item]');

        if (row && ! $event.target.closest('button, a, input, label, select, textarea, [role=switch]')) {
            $wire.mountTableAction('edit', row.getAttribute('x-sortable-item'));
        }
        JS;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'El título grande de la sección en la portada, encima de sus productos, por ejemplo «Stickers Manicure». Algunas secciones con diseño especial, como Ofertas y Novedades, usan un título fijo.')
                    ->placeholder('Stickers Manicure')
                    ->required()
                    ->maxLength(255),
                TextInput::make('subtitle')
                    ->label('Subtítulo')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Una frase corta en letra más pequeña debajo del título, por ejemplo «Diseños nuevos cada semana». Es opcional.')
                    ->maxLength(255),
                Select::make('type')
                    ->label('Tipo de sección')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Decide qué se muestra en esta sección: las ofertas, las novedades que marcaste en cada producto, o los productos de una categoría. Cada opción explica qué incluye.')
                    ->options(HomeSectionType::class)
                    ->required()
                    ->live(),
                Select::make('category_id')
                    ->label('Categoría')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'La categoría cuyos productos se muestran en esta sección.')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => self::typeOf($get) === HomeSectionType::Category)
                    ->required(fn (Get $get): bool => self::typeOf($get) === HomeSectionType::Category),
                TextInput::make('products_limit')
                    ->label('Cuántos productos')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Cuántos productos se muestran como máximo en esta sección, de 1 a 24. Si hay menos, se muestran los que haya. Algunas secciones con diseño especial muestran una cantidad fija.')
                    ->integer()
                    ->minValue(1)
                    ->maxValue(24)
                    ->default(8)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Visible en la portada')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Apagada, la sección deja de verse en la portada, pero queda guardada aquí para volver a encenderla.')
                    // Label on top, like the inputs, so the switch lines up with the field beside it
                    ->inline(false)
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('category'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            // The table always stays in reorder mode, so the button that leaves it is hidden.
            // Filament prints it even when hidden(), hence the theme class.
            ->reorderRecordsTriggerAction(fn (Action $action): Action => $action->extraAttributes(['class' => 'hade-always-reordering'], merge: true))
            ->description('Este es el orden en que se ven en la portada. Toca una sección para editarla, o mantenla presionada y arrástrala para cambiarla de lugar.')
            ->extraAttributes([
                'class' => 'hade-click-to-edit',
                'x-on:pointerdown.capture' => self::HOLD_TO_DRAG,
                'x-on:click' => self::CLICK_TO_EDIT,
            ], merge: true)
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->description(fn (HomeSection $record): ?string => $record->subtitle),
                TextColumn::make('type')
                    ->label('Tipo de sección')
                    ->badge()
                    ->description(fn (HomeSection $record): ?string => $record->category?->name),
                TextColumn::make('products_limit')
                    ->label('Productos'),
                ToggleColumn::make('is_active')
                    ->label('Visible'),
                ViewColumn::make('edit')
                    ->label('')
                    ->view('filament.tables.edit-button'),
            ])
            // Reorder mode hides row buttons: the edit column opens this action, and deleting
            // lives inside its window.
            ->recordActions([
                EditAction::make()
                    ->extraModalFooterActions(fn (EditAction $action): array => [
                        DeleteAction::make()
                            ->record($action->getRecord())
                            ->cancelParentActions(),
                    ]),
            ]);
    }

    public static function getBreadcrumb(): string
    {
        return 'Secciones de portada';
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
