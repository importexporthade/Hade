<?php

namespace App\Console\Commands;

use App\Support\TiendadaCatalog;
use App\Support\TiendadaImport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;

#[Signature('hade:import-tiendada
    {--url='.TiendadaImport::STORE_URL.' : Dirección de la tienda en Tiendada}
    {--store='.TiendadaImport::STORE_ID.' : Código de la tienda en Tiendada}')]
#[Description('Copia el catálogo, las fotos, el logo y la cabecera desde la tienda en Tiendada')]
class ImportTiendadaCommand extends Command
{
    /**
     * Execute the console command. The manager's IT Admin page runs the same import in batches.
     */
    public function handle(): int
    {
        $import = new TiendadaImport(new TiendadaCatalog((string) $this->option('url'), (string) $this->option('store')));

        $this->info('Copiando productos y fotos…');

        try {
            $import->run();
        } catch (RequestException $exception) {
            $this->error('No se pudo leer el catálogo de Tiendada: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$import->totalProducts} productos en Tiendada.");
        $this->info("Productos nuevos: {$import->createdProducts}. Productos completados: {$import->completedProducts}. Fotos descargadas: {$import->downloadedImages}.");

        foreach ($import->problems as $problem) {
            $this->warn($problem);
        }

        return self::SUCCESS;
    }
}
