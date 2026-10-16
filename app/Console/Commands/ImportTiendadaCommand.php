<?php

namespace App\Console\Commands;

use App\Enums\ImageKind;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\ImageOptimizer;
use App\Support\TiendadaCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Signature('hade:import-tiendada
    {--url=https://hade.tiendada.com : Dirección de la tienda en Tiendada}
    {--store=S-46275694 : Código de la tienda en Tiendada}')]
#[Description('Copia el catálogo, las fotos, el logo y la cabecera desde la tienda en Tiendada')]
class ImportTiendadaCommand extends Command
{
    private int $createdProducts = 0;

    private int $completedProducts = 0;

    private int $downloadedImages = 0;

    /** @var list<string> */
    private array $problems = [];

    /**
     * Execute the console command.
     *
     * Running it again is safe: products and categories are matched by name, and only what
     * is still empty here gets filled, so nothing the owner already edited is overwritten.
     */
    public function handle(): int
    {
        $catalog = new TiendadaCatalog((string) $this->option('url'), (string) $this->option('store'));

        try {
            $products = $catalog->products();
        } catch (RequestException $exception) {
            $this->error('No se pudo leer el catálogo de Tiendada: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(count($products).' productos en Tiendada.');

        $details = [];
        $this->withProgressBar($products, function (array $product) use ($catalog, &$details): void {
            try {
                $details[$product['id']] = $catalog->productDetails($product['id']);
            } catch (Throwable) {
                $this->problems[] = "No se pudo leer la página de {$product['name']}.";
            }
        });
        $this->newLine();

        $categories = $this->importCategories($catalog, $details);

        $this->info('Copiando productos y fotos…');
        $progressBar = $this->output->createProgressBar(count($products));

        foreach ($products as $position => $product) {
            if (isset($details[$product['id']])) {
                $this->importProduct($catalog, $product, $details[$product['id']], $categories, $position);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->importStoreImages($catalog);

        $this->info("Productos nuevos: {$this->createdProducts}. Productos completados: {$this->completedProducts}. Fotos descargadas: {$this->downloadedImages}.");

        foreach ($this->problems as $problem) {
            $this->warn($problem);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{category_id: ?string, category_name: ?string}>  $details
     * @return array<string, Category> keyed by Tiendada category code
     */
    private function importCategories(TiendadaCatalog $catalog, array $details): array
    {
        $categoryIds = collect($details)->pluck('category_id')->filter()->unique()->values()->all();

        try {
            $remoteCategories = $catalog->categories($categoryIds);
        } catch (Throwable) {
            $this->problems[] = 'No se pudieron leer las fotos de las categorías.';
            $remoteCategories = collect($details)
                ->filter(fn (array $detail): bool => filled($detail['category_id']))
                ->map(fn (array $detail): array => ['id' => $detail['category_id'], 'name' => $detail['category_name'], 'image_url' => null])
                ->unique('id')
                ->values()
                ->all();
        }

        $nextPosition = (int) Category::query()->max('sort_order');

        return collect($remoteCategories)->mapWithKeys(function (array $remote) use ($catalog, &$nextPosition): array {
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($remote['name'])],
                ['name' => $remote['name'], 'sort_order' => ++$nextPosition],
            );

            if (blank($category->image) && $remote['image_url']) {
                $category->update(['image' => $this->storeImage($catalog, $remote['image_url'], ImageKind::Category)]);
            }

            return [$remote['id'] => $category];
        })->all();
    }

    /**
     * @param  array{id: string, name: string, price: float}  $remote
     * @param  array{images: list<string>, description: ?string, offer_price: ?float, in_stock: bool, category_id: ?string}  $detail
     * @param  array<string, Category>  $categories
     */
    private function importProduct(TiendadaCatalog $catalog, array $remote, array $detail, array $categories, int $position): void
    {
        $category = $categories[$detail['category_id'] ?? ''] ?? null;
        $product = Product::query()->firstWhere('slug', Str::slug($remote['name']));

        if ($product === null) {
            $isOnSale = $detail['offer_price'] !== null && $detail['offer_price'] < $remote['price'];

            $product = Product::query()->create([
                'name' => $remote['name'],
                'slug' => Str::slug($remote['name']),
                'category_id' => $category?->id,
                'price' => $isOnSale ? $detail['offer_price'] : $remote['price'],
                'compare_at_price' => $isOnSale ? $remote['price'] : null,
                'is_in_stock' => $detail['in_stock'],
                'sort_order' => $position + 1,
                'images' => [],
            ]);

            $this->createdProducts++;
        } else {
            $this->completedProducts++;
        }

        $missing = array_filter([
            'category_id' => $product->category_id === null ? $category?->id : null,
            'description' => blank(strip_tags((string) $product->description)) && $detail['description'] ? $this->descriptionHtml($detail['description']) : null,
        ]);

        if (blank($product->images)) {
            $missing['images'] = collect($detail['images'])
                ->map(fn (string $url): ?string => $this->storeImage($catalog, $url, ImageKind::Product))
                ->filter()
                ->values()
                ->all();
        }

        $product->update($missing);
    }

    private function importStoreImages(TiendadaCatalog $catalog): void
    {
        $settings = StoreSetting::current();

        try {
            $images = $catalog->storeImages();
        } catch (Throwable) {
            $this->problems[] = 'No se pudo leer el logo ni la cabecera.';

            return;
        }

        $settings->update(array_filter([
            'logo' => blank($settings->logo) && $images['logo'] ? $this->storeImage($catalog, $images['logo'], ImageKind::Logo) : null,
            'header_image' => blank($settings->header_image) && $images['header_image'] ? $this->storeImage($catalog, $images['header_image'], ImageKind::Header) : null,
        ]));
    }

    /**
     * Saves a downloaded image on the public disk as optimized WebP and returns its path.
     */
    private function storeImage(TiendadaCatalog $catalog, string $url, ImageKind $kind): ?string
    {
        $image = rescue(fn () => $catalog->downloadImage($url), report: false);

        if ($image === null) {
            $this->problems[] = "No se pudo descargar la imagen {$url}.";

            return null;
        }

        $path = ImageOptimizer::store($image['contents'], $kind);

        if ($path === null) {
            $path = $kind->directory().'/'.Str::uuid().".{$image['extension']}";
            Storage::disk('public')->put($path, $image['contents']);
        }

        $this->downloadedImages++;

        return $path;
    }

    /**
     * Turns the plain-text description from Tiendada into the HTML the rich editor keeps:
     * lines starting with "-" or "•" become a list, the rest paragraphs.
     */
    private function descriptionHtml(string $text): string
    {
        $html = '';
        $listItems = [];

        $flushList = function () use (&$html, &$listItems): void {
            if ($listItems !== []) {
                $html .= '<ul>'.implode('', array_map(fn (string $item): string => '<li>'.e($item).'</li>', $listItems)).'</ul>';
                $listItems = [];
            }
        };

        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim($line);

            if (preg_match('/^[-•·]\s*(.+)$/u', $line, $match)) {
                $listItems[] = trim($match[1]);

                continue;
            }

            $flushList();

            if ($line !== '') {
                $html .= '<p>'.e($line).'</p>';
            }
        }

        $flushList();

        return $html;
    }
}
