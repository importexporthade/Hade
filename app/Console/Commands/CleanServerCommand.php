<?php

namespace App\Console\Commands;

use App\Enums\ImageKind;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Frees space on the server without SSH: photos no product, category, banner or store
 * setting uses any more (left by failed or replaced uploads), and the error log. Run alone
 * it only reports what it found; with --borrar it deletes it, and also empties Laravel's
 * compiled views and cache, which rebuild themselves on the next visit.
 */
#[Signature('hade:clean-server {--borrar : Borra lo encontrado en lugar de solo listarlo}')]
#[Description('Lista (o borra con --borrar) las fotos sin usar y vacía el log de errores y las cachés')]
class CleanServerCommand extends Command
{
    /**
     * A photo is saved to disk a moment before its product is saved, so recent files are
     * left alone in case the owner is in the middle of saving one.
     */
    public const int RECENT_MINUTES = 60;

    public function handle(): int
    {
        $deleting = (bool) $this->option('borrar');
        $disk = Storage::disk('public');

        $unusedPhotos = $this->unusedPhotos();
        $photoBytes = $unusedPhotos->sum(fn (string $path): int => $disk->size($path));

        $this->info(sprintf('Fotos sin usar: %d (%s).', $unusedPhotos->count(), $this->megabytes($photoBytes)));
        $unusedPhotos->take(30)->each(fn (string $path) => $this->line("  {$path}"));

        if ($unusedPhotos->count() > 30) {
            $this->line('  … y '.($unusedPhotos->count() - 30).' más.');
        }

        $logs = File::glob(storage_path('logs/*.log'));
        $logBytes = collect($logs)->sum(fn (string $log): int => File::size($log));
        $this->info(sprintf('Log de errores: %s.', $this->megabytes($logBytes)));

        if (! $deleting) {
            $this->comment('No se borró nada. Vuelve a ejecutarlo con --borrar para limpiar.');

            return self::SUCCESS;
        }

        $disk->delete($unusedPhotos->all());
        collect($logs)->each(fn (string $log) => File::put($log, ''));
        $this->callSilently('optimize:clear');

        $this->info(sprintf('Listo: se liberaron %s.', $this->megabytes($photoBytes + $logBytes)));

        return self::SUCCESS;
    }

    /**
     * Files in the photo folders that no record points to, oldest uploads only.
     *
     * @return Collection<int, string>
     */
    private function unusedPhotos(): Collection
    {
        $disk = Storage::disk('public');
        $inUse = $this->photosInUse();
        $recent = now()->subMinutes(self::RECENT_MINUTES)->getTimestamp();

        return collect(ImageKind::cases())
            ->map(fn (ImageKind $kind): string => $kind->directory())
            ->unique()
            ->flatMap(fn (string $directory): array => $disk->allFiles($directory))
            ->reject(fn (string $path): bool => $inUse->has($path) || $disk->lastModified($path) > $recent)
            ->values();
    }

    /**
     * @return Collection<string, true>
     */
    private function photosInUse(): Collection
    {
        return collect([Product::class, Category::class, Banner::class])
            ->flatMap(fn (string $model): Collection => $model::query()->get())
            ->push(StoreSetting::current())
            ->flatMap(fn (Product|Category|Banner|StoreSetting $record): array => $record->storedImageFiles())
            ->mapWithKeys(fn (string $path): array => [$path => true]);
    }

    private function megabytes(int $bytes): string
    {
        return number_format($bytes / 1024 / 1024, 1).' MB';
    }
}
