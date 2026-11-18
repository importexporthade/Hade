<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\User;
use App\Support\AppCaches;
use App\Support\TestCatalog;
use App\Support\TiendadaImport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Locked;
use Throwable;
use UnitEnum;

/**
 * The IT team's page, opened only by an IT admin account: Laravel's caches rebuilt on demand and
 * the pending migrations applied after a release, since Hostinger offers no terminal to run
 * artisan. Until they are retired, it also holds the made-up catalog data and the Tiendada import.
 */
class ItAdmin extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Herramientas';

    protected static ?string $navigationLabel = 'IT Admin';

    protected static ?string $title = 'IT Admin';

    protected static ?string $slug = 'it-admin';

    /**
     * Neither the store owner, who never needs these tools, nor any other account sees the page.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isItAdmin();
    }

    /**
     * Seconds of work per request of the Tiendada import, so the host never cuts one short.
     */
    private const int IMPORT_BATCH_SECONDS = 15;

    /**
     * Progress of the Tiendada import while its batches run; null when none is running.
     *
     * @var array{handled: list<string>, created: int, completed: int, images: int, problems: list<string>, remaining: int}|null
     */
    #[Locked]
    public ?array $tiendadaImport = null;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Datos de prueba')
                    ->description('Categorías, productos, banners y secciones de portada inventados, para probar la tienda y el manager. Llevan «[Prueba]» en el nombre y se borran todos juntos.')
                    ->schema([
                        TextEntry::make('testData')
                            ->hiddenLabel()
                            ->state(fn (TestCatalog $catalog): string => array_sum($catalog->counts()) === 0
                                ? 'No hay datos de prueba.'
                                : 'Ahora hay '.TestCatalog::describe($catalog->counts()).' de prueba.'),
                    ])
                    ->footer([
                        $this->createTestDataAction(),
                        $this->deleteTestDataAction(),
                    ]),
                Section::make('Cachés de Laravel')
                    ->description('Tras cada deploy o cambio del .env se rehacen solas en un minuto. Usa estos botones si un cambio no aparece.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('deployedCommit')
                                ->label('Versión desplegada')
                                ->state(fn (AppCaches $caches): string => substr((string) $caches->deployedCommit(), 0, 7) ?: 'Desconocida'),
                            TextEntry::make('cachesUpToDate')
                                ->label('Cachés al día')
                                ->state(fn (AppCaches $caches): string => $caches->areUpToDate() ? 'Sí' : 'No')
                                ->badge()
                                ->color(fn (string $state): string => $state === 'Sí' ? 'success' : 'warning'),
                            TextEntry::make('configCached')
                                ->label('Configuración en caché')
                                ->state(fn (): string => app()->configurationIsCached() ? 'Sí' : 'No'),
                            TextEntry::make('routesCached')
                                ->label('Rutas en caché')
                                ->state(fn (): string => app()->routesAreCached() ? 'Sí' : 'No'),
                        ]),
                    ])
                    ->footer([
                        $this->rebuildViewsAction(),
                        $this->rebuildRoutesAction(),
                        $this->rebuildConfigAction(),
                    ]),
                Section::make('Base de datos')
                    ->description('Un deploy trae los cambios de la base de datos pero no los aplica. Mientras haya alguno pendiente, guardar en el manager puede fallar.')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('pendingMigrations')
                            ->hiddenLabel()
                            ->state(fn (): string => self::pendingMigrations() === []
                                ? 'Está al día: no hay cambios pendientes.'
                                : count(self::pendingMigrations()).' pendientes: '.implode(', ', self::pendingMigrations()))
                            ->color(fn (): string => self::pendingMigrations() === [] ? 'success' : 'warning'),
                    ])
                    ->footer([
                        $this->runMigrationsAction(),
                    ]),
                Section::make('Productos de Tiendada')
                    ->description('Copia de hade.tiendada.com los productos con sus fotos, descripciones y categorías, además del logo y la cabecera. Los productos que ya tienen fotos aquí no se tocan.')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('tiendadaImportStatus')
                            ->hiddenLabel()
                            ->state(fn (): string => $this->tiendadaImport === null
                                ? 'Ahora hay '.Product::query()->count().' productos en la tienda.'
                                : 'Copiando… van '.count($this->tiendadaImport['handled']).' productos y faltan '.$this->tiendadaImport['remaining'].'. Deja esta pestaña abierta.')
                            ->color(fn (): ?string => $this->tiendadaImport === null ? null : 'warning'),
                    ])
                    ->footer([
                        $this->importTiendadaAction(),
                    ]),
            ]);
    }

    /**
     * The migrations in the deployed code that the database has not run yet.
     *
     * @return list<string>
     */
    public static function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles([database_path('migrations'), ...$migrator->paths()]);
        $ran = $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];

        return array_values(array_diff(array_keys($files), $ran));
    }

    /**
     * Runs "migrate --force" in this same request, in place of the one-off hPanel cron.
     */
    public function runMigrationsAction(): Action
    {
        return Action::make('runMigrations')
            ->label('Aplicar migraciones')
            ->icon(Heroicon::OutlinedCircleStack)
            ->requiresConfirmation()
            ->modalHeading('¿Aplicar los cambios pendientes de la base de datos?')
            ->modalDescription(fn (): string => 'Se aplicarán: '.implode(', ', self::pendingMigrations()).'. Tus productos y datos se conservan.')
            ->modalSubmitActionLabel('Sí, aplicar')
            ->disabled(fn (): bool => self::pendingMigrations() === [])
            ->action(function (): void {
                $pending = count(self::pendingMigrations());

                try {
                    Artisan::call('migrate', ['--force' => true]);
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('No se pudieron aplicar')
                        ->body($exception->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Base de datos al día')
                    ->body($pending === 1 ? 'Se aplicó 1 migración.' : "Se aplicaron {$pending} migraciones.")
                    ->send();
            });
    }

    public function importTiendadaAction(): Action
    {
        return Action::make('importTiendada')
            ->label('Importar desde Tiendada')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->requiresConfirmation()
            ->modalHeading('¿Copiar los productos de Tiendada?')
            ->modalDescription('Se copian por tandas y puede tardar unos minutos: deja esta pestaña abierta hasta que avise que terminó. Si se corta, vuelve a presionar y sigue donde quedó.')
            ->modalSubmitActionLabel('Sí, importar')
            ->disabled(fn (): bool => $this->tiendadaImport !== null)
            ->action(function (): void {
                $this->tiendadaImport = ['handled' => [], 'created' => 0, 'completed' => 0, 'images' => 0, 'problems' => [], 'remaining' => 0];

                $this->importNextTiendadaBatch();
            });
    }

    /**
     * Copies the next batch of products and asks the browser for the following one until none
     * are left, since a single request for the whole catalog would outlast the host's limit.
     */
    public function importNextTiendadaBatch(): void
    {
        if ($this->tiendadaImport === null) {
            return;
        }

        $import = TiendadaImport::fromHadeStore();

        try {
            $import->run($this->tiendadaImport['handled'], self::IMPORT_BATCH_SECONDS);
        } catch (Throwable $exception) {
            report($exception);
            $this->tiendadaImport = null;

            Notification::make()
                ->danger()
                ->title('No se pudo seguir importando')
                ->body('Lo copiado hasta ahora se queda. Vuelve a presionar «Importar desde Tiendada» para seguir. ('.$exception->getMessage().')')
                ->persistent()
                ->send();

            return;
        }

        $progress = [
            'handled' => [...$this->tiendadaImport['handled'], ...$import->handledProducts],
            'created' => $this->tiendadaImport['created'] + $import->createdProducts,
            'completed' => $this->tiendadaImport['completed'] + $import->completedProducts,
            'images' => $this->tiendadaImport['images'] + $import->downloadedImages,
            'problems' => [...$this->tiendadaImport['problems'], ...$import->problems],
            'remaining' => $import->remainingProducts,
        ];

        if ($import->remainingProducts > 0) {
            $this->tiendadaImport = $progress;
            $this->js('$wire.importNextTiendadaBatch()');

            return;
        }

        $this->tiendadaImport = null;

        $summary = "Productos nuevos: {$progress['created']}. Completados: {$progress['completed']}. Fotos copiadas: {$progress['images']}.";
        $problems = $progress['problems'] === [] ? '' : ' Problemas: '.implode(' ', array_slice($progress['problems'], 0, 5))
            .(count($progress['problems']) > 5 ? ' Y '.(count($progress['problems']) - 5).' más.' : '');

        Notification::make()
            ->status($progress['problems'] === [] ? 'success' : 'warning')
            ->title('Importación terminada')
            ->body($summary.$problems)
            ->persistent()
            ->send();
    }

    public function createTestDataAction(): Action
    {
        return Action::make('createTestData')
            ->label('Crear datos de prueba')
            ->icon(Heroicon::OutlinedBeaker)
            ->modalHeading('Crear datos de prueba')
            ->modalDescription('Cada categoría trae una sección de portada y productos de todos los casos: en oferta, agotado, novedad y con un nombre largo. Los banners llevan a la primera categoría.')
            ->modalSubmitActionLabel('Crear')
            ->schema([
                Grid::make(3)->schema([
                    TextInput::make('categories')
                        ->label('Categorías')
                        ->integer()
                        ->minValue(1)
                        ->maxValue(10)
                        ->default(3)
                        ->required(),
                    TextInput::make('productsPerCategory')
                        ->label('Productos por categoría')
                        ->integer()
                        ->minValue(1)
                        ->maxValue(20)
                        ->default(5)
                        ->required(),
                    TextInput::make('banners')
                        ->label('Banners')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(5)
                        ->default(2)
                        ->required(),
                ]),
                Toggle::make('visibleInStore')
                    ->label('Que se vean en la tienda')
                    ->helperText('Apagado, solo los ves en el manager. Prendido, también los ven tus clientas hasta que los borres.')
                    ->default(false),
            ])
            ->action(function (array $data, TestCatalog $catalog): void {
                $created = $catalog->create(
                    (int) $data['categories'],
                    (int) $data['productsPerCategory'],
                    (int) $data['banners'],
                    (bool) $data['visibleInStore'],
                );

                Notification::make()
                    ->success()
                    ->title('Datos de prueba creados')
                    ->body('Se crearon '.TestCatalog::describe($created).'.')
                    ->send();
            });
    }

    public function deleteTestDataAction(): Action
    {
        return Action::make('deleteTestData')
            ->label('Borrar datos de prueba')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('¿Borrar los datos de prueba?')
            ->modalDescription(fn (TestCatalog $catalog): string => 'Se borrarán '.TestCatalog::describe($catalog->counts()).'. Tus productos reales no se tocan.')
            ->modalSubmitActionLabel('Sí, borrar')
            ->modalSubmitAction(fn (Action $action): Action => $action->icon(Heroicon::OutlinedTrash))
            ->disabled(fn (TestCatalog $catalog): bool => array_sum($catalog->counts()) === 0)
            ->action(function (TestCatalog $catalog): void {
                $deleted = $catalog->delete();

                Notification::make()
                    ->success()
                    ->title('Datos de prueba borrados')
                    ->body('Se borraron '.TestCatalog::describe($deleted).'.')
                    ->send();
            });
    }

    public function rebuildViewsAction(): Action
    {
        return $this->rebuildCachesButton('rebuildViews', 'Rehacer vistas', 'view:clear y view:cache, cuando subes cambios de las vistas.', Heroicon::OutlinedEye,
            fn (AppCaches $caches) => $caches->rebuildViews(), 'Vistas rehechas');
    }

    public function rebuildRoutesAction(): Action
    {
        return $this->rebuildCachesButton('rebuildRoutes', 'Rehacer rutas', 'route:clear y route:cache, cuando subes cambios de las rutas.', Heroicon::OutlinedMap,
            fn (AppCaches $caches) => $caches->rebuildRoutes(), 'Rutas rehechas');
    }

    public function rebuildConfigAction(): Action
    {
        return $this->rebuildCachesButton('rebuildConfig', 'Releer el .env', 'cache:clear, config:clear y config:cache, cuando cambias el .env.', Heroicon::OutlinedCog6Tooth,
            fn (AppCaches $caches) => $caches->rebuildConfig(), 'Configuración y caché rehechas');
    }

    /**
     * A button that runs artisan commands in this same request, since Hostinger cannot start
     * a separate process, and reports what went wrong instead of a blank error page.
     */
    private function rebuildCachesButton(string $name, string $label, string $tooltip, Heroicon $icon, callable $rebuild, string $done): Action
    {
        return Action::make($name)
            ->label($label)
            ->tooltip($tooltip)
            ->icon($icon)
            ->color('gray')
            ->action(function (AppCaches $caches) use ($rebuild, $done): void {
                try {
                    $rebuild($caches);
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('No se pudo terminar')
                        ->body($exception->getMessage())
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()->success()->title($done)->send();
            });
    }
}
