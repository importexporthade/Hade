<?php

namespace App\Console\Commands;

use App\Support\AppCaches;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('hade:refresh-caches {--force : Las rehace aunque no haya cambios}')]
#[Description('Rehace las cachés de configuración, rutas y vistas cuando llega un deploy nuevo o cambia el .env')]
class RefreshCachesCommand extends Command
{
    /**
     * Runs every minute from the scheduler, and does nothing until something changed.
     */
    public function handle(AppCaches $caches): int
    {
        if (! $this->option('force') && $caches->areUpToDate()) {
            return self::SUCCESS;
        }

        $caches->rebuild();

        $this->info('Cachés rehechas para la versión '.$caches->currentVersion().'.');

        return self::SUCCESS;
    }
}
