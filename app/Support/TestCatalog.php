<?php

namespace App\Support;

use App\Enums\BannerLinkType;
use App\Enums\HomeSectionType;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Made-up categories, products, banners and home sections for trying the store and the
 * manager, on the live site too. They carry "[Prueba]" in their names and "prueba-" in their
 * links, which is how they are told apart from the real catalog and deleted all at once.
 * Plain data, like the StoreSeeder, since Faker only comes with the development packages.
 */
class TestCatalog
{
    public const string NAME_PREFIX = '[Prueba] ';

    public const string SLUG_PREFIX = 'prueba-';

    private const array CATEGORY_NAMES = ['Stickers Aurora', 'Stickers Brisa', 'Kits Coral', 'Lámparas Duna', 'Accesorios Estela'];

    /**
     * Products repeated in every category, each trying a different case of the cards:
     * [name, price, regular price when on sale, in stock, marked as new].
     *
     * @var list<array{0: string, 1: float, 2: float|null, 3: bool, 4: bool}>
     */
    private const array PRODUCTS = [
        ['Clásico', 35.00, null, true, false],
        ['En oferta', 59.90, 89.00, true, false],
        ['Agotado', 40.00, null, false, false],
        ['Novedad', 45.00, null, true, true],
        ['Con un nombre muy largo para ver cómo se acomoda en las tarjetas', 129.90, 190.00, true, true],
    ];

    /** Photo shipped with the store, copied for each test banner. */
    private const string BANNER_PHOTO = 'images/hero-nail-stickers.jpg';

    /**
     * @return array{categories: int, products: int, banners: int, sections: int}
     */
    public function create(int $categories, int $productsPerCategory, int $banners, bool $visibleInStore): array
    {
        return DB::transaction(function () use ($categories, $productsPerCategory, $banners, $visibleInStore): array {
            $run = Str::lower(Str::random(4));
            $sortOrder = (int) Product::query()->max('sort_order');
            $sectionOrder = (int) HomeSection::query()->max('sort_order');
            $created = ['categories' => 0, 'products' => 0, 'banners' => 0, 'sections' => 0];
            $firstCategory = null;

            foreach (range(1, $categories) as $number) {
                $name = self::CATEGORY_NAMES[($number - 1) % count(self::CATEGORY_NAMES)];
                $category = Category::query()->create([
                    'name' => self::NAME_PREFIX.$name,
                    'slug' => self::SLUG_PREFIX."{$run}-".Str::slug($name)."-{$number}",
                    'description' => 'Categoría de prueba creada desde Debug.',
                    'is_visible' => $visibleInStore,
                    'sort_order' => 100 + $number,
                ]);
                $firstCategory ??= $category;
                $created['categories']++;

                foreach (range(1, $productsPerCategory) as $position) {
                    [$productName, $price, $regularPrice, $inStock, $isNew] = self::PRODUCTS[($position - 1) % count(self::PRODUCTS)];

                    Product::query()->create([
                        'category_id' => $category->id,
                        'name' => self::NAME_PREFIX."{$productName} {$number}.{$position}",
                        'slug' => self::SLUG_PREFIX."{$run}-".Str::slug($productName)."-{$number}-{$position}",
                        'description' => '<p>Producto de prueba creado desde Debug. Bórralo cuando termines.</p>',
                        'price' => $price,
                        'compare_at_price' => $regularPrice,
                        'images' => [],
                        'is_visible' => $visibleInStore,
                        'is_in_stock' => $inStock,
                        'is_featured' => $isNew,
                        'sort_order' => ++$sortOrder,
                    ]);
                    $created['products']++;
                }

                HomeSection::query()->create([
                    'title' => self::NAME_PREFIX.$name,
                    'type' => HomeSectionType::Category,
                    'category_id' => $category->id,
                    'is_active' => $visibleInStore,
                    'sort_order' => ++$sectionOrder,
                ]);
                $created['sections']++;
            }

            foreach ($banners > 0 ? range(1, $banners) : [] as $number) {
                $image = 'banners/'.self::SLUG_PREFIX."{$run}-{$number}.jpg";
                Storage::disk('public')->put($image, File::get(public_path(self::BANNER_PHOTO)));

                Banner::query()->create([
                    'title' => self::NAME_PREFIX."Banner {$number}",
                    'subtitle' => 'Banner de prueba creado desde Debug.',
                    'image' => $image,
                    'link_type' => $firstCategory ? BannerLinkType::Category : BannerLinkType::None,
                    'link_category_id' => $firstCategory?->id,
                    'button_label' => 'Ver la categoría',
                    'is_active' => $visibleInStore,
                    'sort_order' => 100 + $number,
                ]);
                $created['banners']++;
            }

            return $created;
        });
    }

    /**
     * Deletes every test record, one by one so their photos leave the disk with them.
     *
     * @return array{categories: int, products: int, banners: int, sections: int}
     */
    public function delete(): array
    {
        $deleted = $this->counts();

        DB::transaction(function (): void {
            $this->sections()->get()->each->delete();
            $this->banners()->get()->each->delete();
            $this->products()->get()->each->delete();
            $this->categories()->get()->each->delete();
        });

        return $deleted;
    }

    /**
     * @return array{categories: int, products: int, banners: int, sections: int}
     */
    public function counts(): array
    {
        return [
            'categories' => $this->categories()->count(),
            'products' => $this->products()->count(),
            'banners' => $this->banners()->count(),
            'sections' => $this->sections()->count(),
        ];
    }

    /**
     * "3 categorías, 12 productos, 2 banners y 3 secciones".
     *
     * @param  array{categories: int, products: int, banners: int, sections: int}  $counts
     */
    public static function describe(array $counts): string
    {
        $parts = [
            $counts['categories'].' '.($counts['categories'] === 1 ? 'categoría' : 'categorías'),
            $counts['products'].' '.($counts['products'] === 1 ? 'producto' : 'productos'),
            $counts['banners'].' '.($counts['banners'] === 1 ? 'banner' : 'banners'),
            $counts['sections'].' '.($counts['sections'] === 1 ? 'sección' : 'secciones'),
        ];

        return Str::replaceLast(', ', ' y ', implode(', ', $parts));
    }

    /**
     * @return Builder<Category>
     */
    private function categories(): Builder
    {
        return Category::query()->where('slug', 'like', self::SLUG_PREFIX.'%');
    }

    /**
     * @return Builder<Product>
     */
    private function products(): Builder
    {
        return Product::query()->where('slug', 'like', self::SLUG_PREFIX.'%');
    }

    /**
     * @return Builder<Banner>
     */
    private function banners(): Builder
    {
        return Banner::query()->where('title', 'like', self::NAME_PREFIX.'%');
    }

    /**
     * @return Builder<HomeSection>
     */
    private function sections(): Builder
    {
        return HomeSection::query()->where('title', 'like', self::NAME_PREFIX.'%');
    }
}
