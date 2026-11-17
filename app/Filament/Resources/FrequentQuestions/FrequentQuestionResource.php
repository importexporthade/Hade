<?php

namespace App\Filament\Resources\FrequentQuestions;

use App\Filament\Resources\FrequentQuestions\Pages\ManageFrequentQuestions;
use App\Filament\Support\SortableCards;
use App\Models\FrequentQuestion;
use App\Models\StoreSetting;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use UnitEnum;

class FrequentQuestionResource extends Resource
{
    protected static ?string $model = FrequentQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Tienda online';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Preguntas frecuentes';

    protected static ?string $modelLabel = 'pregunta';

    protected static ?string $pluralModelLabel = 'preguntas frecuentes';

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('question')
                    ->label('Pregunta')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Escríbela como te la preguntan tus clientas. Ej: ¿Hacen envíos a provincia?')
                    ->placeholder('¿Hacen envíos a provincia?')
                    ->required()
                    ->maxLength(255),
                RichEditor::make('answer')
                    ->label('Respuesta')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Lo que verán al tocar la pregunta. Puedes usar negritas, enlaces y listas.')
                    ->toolbarButtons([
                        ['bold', 'italic', 'link'],
                        ['bulletList', 'orderedList'],
                        ['undo', 'redo'],
                    ])
                    ->required(),
                Toggle::make('is_active')
                    ->label('Visible en la tienda')
                    ->hintIcon(Heroicon::OutlinedQuestionMarkCircle, tooltip: 'Apágala para esconder esta pregunta sin borrarla. La vuelves a prender cuando quieras.')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return SortableCards::configure($table)
            ->description(fn (): string => StoreSetting::current()->shows_frequent_questions
                ? 'La portada muestra las primeras '.FrequentQuestion::HOME_LIMIT.' preguntas visibles en este orden, y la página "Preguntas frecuentes" las muestra todas. Toca una pregunta para editarla, o mantenla presionada y arrástrala para cambiarla de lugar.'
                : 'La sección está apagada: tus clientas no ven ninguna pregunta en la tienda. Préndela con el botón de arriba cuando quieras mostrarlas.')
            ->emptyStateHeading('Aún no hay preguntas')
            ->emptyStateDescription('Sin preguntas visibles, la sección no aparece en la tienda.')
            ->columns([
                TextColumn::make('question')
                    ->label('Pregunta')
                    ->description(fn (FrequentQuestion $record): string => str($record->plainAnswer())->limit(90)->toString())
                    ->wrap(),
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
        return 'Preguntas frecuentes';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFrequentQuestions::route('/'),
        ];
    }
}
