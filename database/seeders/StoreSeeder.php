<?php

namespace Database\Seeders;

use App\Enums\HomeSectionType;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Page;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * A starter catalog taken from the products featured on hade.tiendada.com in October 2026,
 * without photos. The full catalog with photos comes from `php artisan hade:import-tiendada`.
 */
class StoreSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Products per category as [name, price, regular price when on sale].
     *
     * @var array<string, list<array{0: string, 1: float, 2?: float}>>
     */
    private const array CATALOG = [
        'Stickers Manicure' => [
            ['Crepusculo', 35.00], ['Lunares', 35.00], ['Limonada', 35.00], ['Marina', 35.00],
            ['Pantera', 35.00], ['Encanto Oro', 35.00], ['Midnigth', 35.00],
        ],
        'Stickers Pedicure' => [
            ['Brillos', 40.00], ['Lila', 40.00], ['Colors', 40.00], ['Ada', 40.00], ['Peonias', 40.00],
        ],
        'Kits y Promociones' => [
            ['Kit Hade Recargada', 76.90, 90.00], ['Kit Glow Recargable', 119.90, 177.00],
            ['Duo Hade', 59.90, 76.00], ['Trío Hade', 85.90, 114.00],
            ['Promo Premium', 129.90, 190.00], ['Kit Completo Recargable', 85.90, 105.00],
        ],
        'Lámparas' => [
            ['Lámpara Conectada', 18.00], ['Lámpara Recargable', 22.00],
        ],
        'Accesorios' => [
            ['Base Coat', 12.00], ['Top Coat', 12.00], ['Brillo Efecto gel', 12.00], ['Aceite de cutícula', 8.00],
            ['Guantes Manicure Fucsia', 12.00], ['Guantes Manicure Rosado', 12.00],
            ['Guantes Manicure Negro', 12.00], ['Tijera de Uñas', 10.00],
        ],
    ];

    public function run(): void
    {
        $categories = collect(array_keys(self::CATALOG))->mapWithKeys(fn (string $name, int $position) => [
            $name => Category::query()->create([
                'name' => $name,
                'slug' => Str::slug($name),
                'sort_order' => $position + 1,
            ]),
        ]);

        $position = 0;

        foreach (self::CATALOG as $categoryName => $products) {
            foreach ($products as $product) {
                Product::query()->create([
                    'category_id' => $categories[$categoryName]->id,
                    'name' => $product[0],
                    'slug' => Str::slug($product[0]),
                    'price' => $product[1],
                    'compare_at_price' => $product[2] ?? null,
                    'is_featured' => true,
                    'images' => [],
                    'sort_order' => ++$position,
                ]);
            }
        }

        $sections = [
            ['title' => 'Ofertas', 'type' => HomeSectionType::Offers],
            ['title' => 'Novedades', 'type' => HomeSectionType::Latest, 'products_limit' => 10],
            ['title' => 'Stickers Manicure', 'type' => HomeSectionType::Category, 'category_id' => $categories['Stickers Manicure']->id],
            ['title' => 'Stickers Pedicure', 'type' => HomeSectionType::Category, 'category_id' => $categories['Stickers Pedicure']->id],
            ['title' => 'Kits y Promociones', 'type' => HomeSectionType::Category, 'category_id' => $categories['Kits y Promociones']->id],
            ['title' => 'Lámparas', 'type' => HomeSectionType::Category, 'category_id' => $categories['Lámparas']->id],
        ];

        foreach ($sections as $position => $section) {
            HomeSection::query()->create([...$section, 'sort_order' => $position + 1]);
        }

        Page::query()->create([
            'title' => 'Envíos y delivery',
            'slug' => 'envios-y-delivery',
            'content' => '<p>Hacemos delivery a todo el Perú.</p>',
            'sort_order' => 1,
        ]);

        StoreSetting::current()->update([
            'store_name' => 'Hade',
            'announcement' => 'Envío gratis a todo el Perú',
            'footer_text' => 'En HADE creemos en una manicura elegante, rápida y accesible. Nuestros gel nail stickers se aplican en minutos, duran hasta 10–14 días y se retiran sin dañar. Envío gratis a todo el Perú y descuentos desde la 2ª unidad.',
            'whatsapp_number' => '51 986 000 362',
            'whatsapp_message' => 'Hola. Me gustaría comprar un producto de tu tienda.',
            'email' => 'import.export.hade@gmail.com',
            'phone' => '+51 986 000 362',
            'facebook_url' => 'https://www.facebook.com/share/17VZHrj9U5/',
            'instagram_url' => 'https://www.instagram.com/hade.pe',
            'tiktok_url' => 'https://www.tiktok.com/@hade.pe',
            'youtube_url' => 'https://www.youtube.com/@Hade_pe',
        ]);

        $this->call(InformationPagesSeeder::class);
    }
}
