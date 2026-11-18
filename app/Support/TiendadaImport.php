<?php

namespace App\Support;

use App\Enums\ImageKind;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Copies the catalog, photos, logo and header from the store's previous home on Tiendada.
 *
 * Running it again is safe: products and categories are matched by name, products that
 * already have photos are left alone, and only what is still empty here gets filled, so
 * nothing the owner already edited is overwritten. It can also run in short batches, for
 * the manager's button on a host that cuts long requests.
 */
class TiendadaImport
{
    public const string STORE_URL = 'https://hade.tiendada.com';

    public const string STORE_ID = 'S-46275694';

    public int $createdProducts = 0;

    public int $completedProducts = 0;

    public int $downloadedImages = 0;

    public int $totalProducts = 0;

    /** Products still to copy when a time limit stopped the run. */
    public int $remainingProducts = 0;

    /** @var list<string> */
    public array $problems = [];

    /** @var list<string> Tiendada codes of the products this run went through */
    public array $handledProducts = [];

    /** @var array<string, Category|null> keyed by Tiendada category code */
    private array $categories = [];

    public function __construct(private TiendadaCatalog $catalog) {}

    public static function fromHadeStore(): self
    {
        return new self(new TiendadaCatalog(self::STORE_URL, self::STORE_ID));
    }

    /**
     * Deletes every product, category and banner with their photos, and the home sections that
     * show one of those categories, so an import starts from zero.
     *
     * @return array{categories: int, products: int, banners: int, sections: int}
     */
    public static function deleteCatalog(): array
    {
        $sections = HomeSection::query()->whereNotNull('category_id');

        $deleted = [
            'categories' => Category::query()->count(),
            'products' => Product::query()->count(),
            'banners' => Banner::query()->count(),
            'sections' => $sections->count(),
        ];

        DB::transaction(function () use ($sections): void {
            $sections->get()->each->delete();
            Banner::query()->get()->each->delete();
            Product::query()->get()->each->delete();
            Category::query()->get()->each->delete();
        });

        return $deleted;
    }

    /**
     * Copies the products that are not here yet or still have no photos, then the logo and
     * the header once every product is done.
     *
     * @param  list<string>  $skip  Tiendada codes an earlier batch already went through
     * @param  int|null  $seconds  stop after the product that goes past this time; at least one is always copied
     *
     * @throws RequestException when the product list cannot be read
     */
    public function run(array $skip = [], ?int $seconds = null): void
    {
        $startedAt = microtime(true);
        $products = $this->catalog->products();
        $this->totalProducts = count($products);

        $productsWithPhotos = Product::query()
            ->whereIn('slug', array_map(fn (array $product): string => Str::slug($product['name']), $products))
            ->get(['slug', 'images'])
            ->filter(fn (Product $product): bool => filled($product->images))
            ->pluck('slug')
            ->all();

        $pending = array_filter(
            $products,
            fn (array $product): bool => ! in_array($product['id'], $skip, true)
                && ! in_array(Str::slug($product['name']), $productsWithPhotos, true),
        );

        foreach ($pending as $position => $product) {
            if ($seconds !== null && $this->handledProducts !== [] && microtime(true) - $startedAt >= $seconds) {
                break;
            }

            $this->importProduct($product, $position);
            $this->handledProducts[] = $product['id'];
        }

        $this->remainingProducts = count($pending) - count($this->handledProducts);

        if ($this->remainingProducts === 0) {
            $this->importStoreImages();
        }
    }

    /**
     * @param  array{id: string, name: string, price: float}  $remote
     */
    private function importProduct(array $remote, int $position): void
    {
        try {
            $detail = $this->catalog->productDetails($remote['id']);
        } catch (Throwable) {
            $this->problems[] = "No se pudo leer la página de {$remote['name']}.";

            return;
        }

        $category = $this->category($detail['category_id'], $detail['category_name']);
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
                ->map(fn (string $url): ?string => $this->storeImage($url, ImageKind::Product))
                ->filter()
                ->values()
                ->all();
        }

        $product->update($missing);
    }

    /**
     * The category here for a Tiendada category, created with its photo the first time.
     */
    private function category(?string $code, ?string $name): ?Category
    {
        if (blank($code) || blank($name)) {
            return null;
        }

        if (array_key_exists($code, $this->categories)) {
            return $this->categories[$code];
        }

        $category = Category::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'sort_order' => (int) Category::query()->max('sort_order') + 1],
        );

        if (blank($category->image)) {
            try {
                $imageUrl = collect($this->catalog->categories([$code]))->firstWhere('id', $code)['image_url'] ?? null;
            } catch (Throwable) {
                $this->problems[] = "No se pudo leer la foto de la categoría {$name}.";
                $imageUrl = null;
            }

            if ($imageUrl) {
                $category->update(['image' => $this->storeImage($imageUrl, ImageKind::Category)]);
            }
        }

        return $this->categories[$code] = $category;
    }

    private function importStoreImages(): void
    {
        $settings = StoreSetting::current();

        if (filled($settings->logo) && filled($settings->header_image)) {
            return;
        }

        try {
            $images = $this->catalog->storeImages();
        } catch (Throwable) {
            $this->problems[] = 'No se pudo leer el logo ni la cabecera.';

            return;
        }

        $settings->update(array_filter([
            'logo' => blank($settings->logo) && $images['logo'] ? $this->storeImage($images['logo'], ImageKind::Logo) : null,
            'header_image' => blank($settings->header_image) && $images['header_image'] ? $this->storeImage($images['header_image'], ImageKind::Header) : null,
        ]));
    }

    /**
     * Saves a downloaded image on the public disk as optimized WebP and returns its path.
     */
    private function storeImage(string $url, ImageKind $kind): ?string
    {
        $image = rescue(fn () => $this->catalog->downloadImage($url), report: false);

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
