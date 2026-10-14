<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Reads the store's catalog from its previous home on Tiendada: the public product list
 * API, the category API, and the structured data (JSON-LD) of each product page.
 */
class TiendadaCatalog
{
    private const int PAGE_SIZE = 50;

    private const int MAX_IMAGE_BYTES = 10 * 1024 * 1024;

    /**
     * File extension for each image type the importer accepts.
     *
     * @var array<string, string>
     */
    private const array IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(
        private string $baseUrl,
        private string $storeId,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Every enabled product in the order the store lists them.
     *
     * @return list<array{id: string, name: string, price: float}>
     */
    public function products(): array
    {
        $products = [];
        $page = 0;

        do {
            $response = $this->http()->get('/api/winehouse/product', [
                'store_id' => $this->storeId,
                'enabled' => 1,
                'page' => $page,
                'size' => self::PAGE_SIZE,
            ])->throw()->json();

            foreach ($response['data'] ?? [] as $product) {
                $products[] = [
                    'id' => (string) $product['id'],
                    'name' => trim((string) $product['name']),
                    'price' => (float) $product['price'],
                ];
            }

            $page++;
            $total = (int) ($response['pagination']['total'] ?? 0);
        } while (count($products) < $total && ($response['data'] ?? []) !== []);

        return $products;
    }

    /**
     * The store's categories with the URL of their square photo.
     *
     * @param  list<string>  $categoryIds
     * @return list<array{id: string, name: string, image_url: ?string}>
     */
    public function categories(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $response = $this->http()->get('/api/im/category/product', [
            'store_id' => $this->storeId,
            'categories_ids' => implode(',', $categoryIds),
            'products' => 1,
            'page' => 0,
            'size' => count($categoryIds),
        ])->throw()->json();

        return collect($response['data'] ?? [])
            ->map(fn (array $category): array => [
                'id' => (string) $category['id'],
                'name' => trim((string) $category['name']),
                'image_url' => filled($category['portraitImageId'] ?? null) ? $this->fileUrl($category['portraitImageId']) : null,
            ])
            ->all();
    }

    /**
     * Photos, description, current price, stock and category of one product, read from
     * the structured data its page publishes.
     *
     * @return array{images: list<string>, description: ?string, offer_price: ?float, in_stock: bool, category_id: ?string, category_name: ?string}
     */
    public function productDetails(string $productId): array
    {
        $html = $this->http()->get("/product/{$productId}")->throw()->body();
        $product = [];
        $category = null;

        foreach ($this->structuredData($html) as $data) {
            if (($data['@type'] ?? null) === 'Product') {
                $product = $data;
            }

            if (($data['@type'] ?? null) === 'BreadcrumbList') {
                foreach ($data['itemListElement'] ?? [] as $item) {
                    if (preg_match('#/category/([A-Z0-9-]+)#', (string) ($item['item'] ?? ''), $match)) {
                        $category = ['id' => $match[1], 'name' => trim((string) $item['name'])];
                    }
                }
            }
        }

        $description = isset($product['description'])
            ? trim(str_replace(['\\n', '\n'], "\n", (string) $product['description']))
            : null;

        return [
            'images' => array_values(array_filter(array_map('strval', (array) ($product['image'] ?? [])))),
            'description' => $description !== '' ? $description : null,
            'offer_price' => isset($product['offers']['price']) ? (float) $product['offers']['price'] : null,
            'in_stock' => ! str_contains((string) ($product['offers']['availability'] ?? ''), 'OutOfStock'),
            'category_id' => $category['id'] ?? null,
            'category_name' => $category['name'] ?? null,
        ];
    }

    /**
     * URLs of the round logo and the photo band at the top of the storefront.
     *
     * @return array{logo: ?string, header_image: ?string}
     */
    public function storeImages(): array
    {
        $html = $this->http()->get('/')->throw()->body();

        preg_match('#class="[^"]*headerPoster[^"]*"\s+src="([^"]+)"#', $html, $header);
        preg_match('#<title>([^<]+)</title>#', $html, $title);
        $storeName = preg_quote(html_entity_decode(trim($title[1] ?? '')), '#');
        preg_match("#<img src=\"([^\"]+)\" alt=\"{$storeName}\"#", $html, $logo);

        return [
            'logo' => isset($logo[1]) ? $this->absoluteUrl(html_entity_decode($logo[1])) : null,
            'header_image' => isset($header[1]) ? $this->absoluteUrl(html_entity_decode($header[1])) : null,
        ];
    }

    /**
     * Downloads an image, or returns null when the response is not a usable image.
     *
     * @return array{contents: string, extension: string}|null
     */
    public function downloadImage(string $url): ?array
    {
        $response = $this->http()->get($url);
        $contents = $response->body();

        if ($response->failed() || $contents === '' || strlen($contents) > self::MAX_IMAGE_BYTES) {
            return null;
        }

        $info = @getimagesizefromstring($contents);
        $extension = $info ? (self::IMAGE_TYPES[$info['mime']] ?? null) : null;

        return $extension ? ['contents' => $contents, 'extension' => $extension] : null;
    }

    private function fileUrl(string $fileId): string
    {
        return "{$this->baseUrl}/api/scrooge/file/{$fileId}?v=-2";
    }

    private function absoluteUrl(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : $this->baseUrl.'/'.ltrim($url, '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function structuredData(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return collect($matches[1])
            ->map(fn (string $json): mixed => json_decode(html_entity_decode($json), true))
            ->filter(fn (mixed $data): bool => is_array($data))
            ->values()
            ->all();
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->retry(2, 500, throw: false)
            ->withUserAgent('HadeImporter/1.0');
    }
}
