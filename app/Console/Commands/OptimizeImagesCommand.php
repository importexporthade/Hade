<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\ImageOptimizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('hade:optimize-images')]
#[Description('Convierte a WebP liviano las fotos guardadas antes de que existiera el optimizador')]
class OptimizeImagesCommand extends Command
{
    private int $optimizedImages = 0;

    /**
     * Execute the console command.
     *
     * Each record points to the new WebP file first; the models then delete the photos they
     * no longer use, so an interrupted run never leaves a record without its photo.
     * Photos that are already WebP are skipped, so running it again is safe.
     */
    public function handle(): int
    {
        $bytesBefore = $this->publicDiskSize();

        $products = Product::query()->whereNotNull('images')->get();
        $this->info("Optimizando fotos de {$products->count()} productos…");
        $this->withProgressBar($products, function (Product $product): void {
            $product->update([
                'images' => collect($product->images)
                    ->map(fn (string $path): string => $this->optimized($path, ImageOptimizer::PRODUCT_SIZE, ImageOptimizer::PRODUCT_THUMBNAIL_SIZE))
                    ->all(),
            ]);
        });
        $this->newLine();

        Category::query()->whereNotNull('image')->each(function (Category $category): void {
            $category->update(['image' => $this->optimized($category->image, ImageOptimizer::CATEGORY_SIZE)]);
        });

        Banner::query()->each(function (Banner $banner): void {
            $banner->update([
                'image' => $this->optimized($banner->image, ImageOptimizer::BANNER_SIZE),
                'mobile_image' => $banner->mobile_image ? $this->optimized($banner->mobile_image, ImageOptimizer::MOBILE_BANNER_SIZE) : null,
            ]);
        });

        $settings = StoreSetting::current();
        $settings->update([
            'logo' => $settings->logo ? $this->optimized($settings->logo, ImageOptimizer::LOGO_SIZE) : null,
            'header_image' => $settings->header_image ? $this->optimized($settings->header_image, ImageOptimizer::HEADER_SIZE) : null,
        ]);

        $bytesAfter = $this->publicDiskSize();

        $this->info(sprintf(
            'Fotos optimizadas: %d. Espacio usado: de %s a %s.',
            $this->optimizedImages,
            $this->megabytes($bytesBefore),
            $this->megabytes($bytesAfter),
        ));

        return self::SUCCESS;
    }

    /**
     * Path of the optimized copy of a photo, or the same path when it is already WebP,
     * missing, or not an image GD can read.
     */
    private function optimized(string $path, int $maxSize, ?int $thumbnailSize = null): string
    {
        $disk = Storage::disk('public');

        if (ImageOptimizer::isOptimized($path) || ! $disk->exists($path)) {
            return $path;
        }

        $optimizedPath = ImageOptimizer::store($disk->get($path), dirname($path), $maxSize, $thumbnailSize);

        if ($optimizedPath === null) {
            $this->warn("No se pudo optimizar {$path}.");

            return $path;
        }

        $this->optimizedImages++;

        return $optimizedPath;
    }

    private function publicDiskSize(): int
    {
        $disk = Storage::disk('public');

        return collect($disk->allFiles())->sum(fn (string $file): int => $disk->size($file));
    }

    private function megabytes(int $bytes): string
    {
        return number_format($bytes / 1024 / 1024, 1).' MB';
    }
}
