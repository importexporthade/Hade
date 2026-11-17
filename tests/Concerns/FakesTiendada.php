<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * A Tiendada store with two products: "Kit Hade Recargada" (two photos, on sale, in "Kits y
 * Promociones", which has a photo) and "Lámpara Conectada" (one photo, sold out, in
 * "Lámparas", which has none), plus the logo and the header.
 */
trait FakesTiendada
{
    private function fakeTiendada(string $base, bool $brokenImage = false): void
    {
        $png = UploadedFile::fake()->image('photo.png', 8, 8)->get();

        Http::fake([
            "{$base}/api/winehouse/product*" => Http::response([
                'data' => [
                    ['id' => 'PR-KIT', 'name' => 'Kit Hade Recargada', 'price' => 90],
                    ['id' => 'PR-LAMP', 'name' => 'Lámpara Conectada', 'price' => 18],
                ],
                'pagination' => ['page' => 0, 'size' => 50, 'total' => 2],
            ]),
            "{$base}/api/im/category/product*" => Http::response([
                'data' => [
                    ['id' => 'PC-KITS', 'name' => 'Kits y Promociones', 'portraitImageId' => 'FL-CAT1'],
                    ['id' => 'PC-LAMPS', 'name' => 'Lámparas', 'portraitImageId' => null],
                ],
            ]),
            "{$base}/api/scrooge/file/FL-BROKEN*" => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'text/html']),
            "{$base}/api/scrooge/file/*" => Http::response($png, 200, ['Content-Type' => 'image/png']),
            "{$base}/product/PR-KIT" => Http::response($this->tiendadaProductPage(
                base: $base,
                name: 'Kit Hade Recargada',
                images: ['FL-K1', $brokenImage ? 'FL-BROKEN' : 'FL-K2'],
                description: 'Incluye\\n\\n- Lámpara recargable\\n- Top Coat',
                price: '76.90',
                availability: 'InStock',
                categoryId: 'PC-KITS',
                categoryName: 'Kits y Promociones',
            )),
            "{$base}/product/PR-LAMP" => Http::response($this->tiendadaProductPage(
                base: $base,
                name: 'Lámpara Conectada',
                images: ['FL-L1'],
                description: '',
                price: '18.00',
                availability: 'OutOfStock',
                categoryId: 'PC-LAMPS',
                categoryName: 'Lámparas',
            )),
            "{$base}/" => Http::response(
                '<html><head><title>Hade</title></head><body>'
                .'<img class="style-module__headerPoster" src="/api/scrooge/file/FL-HEADER?v=-2" alt="">'
                .'<img src="/api/scrooge/file/FL-LOGO?v=-2" alt="Hade"/></body></html>'
            ),
        ]);
    }

    /**
     * @param  list<string>  $images
     */
    private function tiendadaProductPage(string $base, string $name, array $images, string $description, string $price, string $availability, string $categoryId, string $categoryName): string
    {
        $product = json_encode([
            '@type' => 'Product',
            'name' => $name,
            'image' => array_map(fn (string $id): string => "{$base}/api/scrooge/file/{$id}?v=-2", $images),
            'description' => $description,
            'offers' => ['@type' => 'Offer', 'price' => $price, 'availability' => "https://schema.org/{$availability}"],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $breadcrumb = json_encode([
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => $categoryName, 'item' => "{$base}/category/{$categoryId}"],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $name],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return "<html><script type=\"application/ld+json\">{$product}</script><script type=\"application/ld+json\">{$breadcrumb}</script></html>";
    }
}
