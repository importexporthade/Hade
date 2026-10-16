<?php

namespace App\Console\Commands;

use App\Enums\ImageKind;
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
#[Description('Convierte las fotos a WebP liviano y genera las copias de cada tamaño que les falten')]
class OptimizeImagesCommand extends Command
{
    private int $convertedImages = 0;

    private int $completedImages = 0;

    /**
     * Execute the console command.
     *
     * Each record points to the new WebP file first; the models then delete the photos they
     * no longer use, so an interrupted run never leaves a record without its photo. Photos
     * already done are skipped, so running it again is safe.
     */
    public function handle(): int
    {
        $bytesBefore = $this->publicDiskSize();

        $products = Product::query()->get();
        $this->info("Revisando fotos de {$products->count()} productos…");
        $this->withProgressBar($products, function (Product $product): void {
            $product->update([
                'images' => collect($product->images ?? [])
                    ->map(fn (string $path): string => $this->optimized($path, ImageKind::Product))
                    ->all(),
                'cover_image' => $product->cover_image ? $this->optimized($product->cover_image, ImageKind::ProductCover) : null,
            ]);
        });
        $this->newLine();

        Category::query()->whereNotNull('image')->each(function (Category $category): void {
            $category->update(['image' => $this->optimized($category->image, ImageKind::Category)]);
        });

        Banner::query()->each(function (Banner $banner): void {
            $banner->update([
                'image' => $this->optimized($banner->image, ImageKind::Banner),
                'mobile_image' => $banner->mobile_image ? $this->optimized($banner->mobile_image, ImageKind::MobileBanner) : null,
            ]);
        });

        $settings = StoreSetting::current();
        $settings->update([
            'logo' => $settings->logo ? $this->optimized($settings->logo, ImageKind::Logo) : null,
            'header_image' => $settings->header_image ? $this->optimized($settings->header_image, ImageKind::Header) : null,
        ]);

        $this->info(sprintf(
            'Fotos convertidas a WebP: %d. Fotos con copias nuevas: %d. Espacio usado: de %s a %s.',
            $this->convertedImages,
            $this->completedImages,
            $this->megabytes($bytesBefore),
            $this->megabytes($this->publicDiskSize()),
        ));

        return self::SUCCESS;
    }

    /**
     * Path of the photo once it is a WebP with every copy of its kind. A photo that is not
     * WebP yet is saved again; one that is gets the copies it lacks, and the "thumbs" copy
     * of the previous system is removed.
     */
    private function optimized(string $path, ImageKind $kind): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $path;
        }

        if (ImageOptimizer::isOptimized($path)) {
            $hadAllCopies = collect($kind->widths())->every(fn (int $width): bool => $disk->exists(ImageOptimizer::variantPath($path, $width)));

            if (! $hadAllCopies && ImageOptimizer::addMissingVariants($path, $kind)) {
                $this->completedImages++;
            }

            $legacyThumbnail = dirname($path).'/thumbs/'.basename($path);
            $disk->delete($legacyThumbnail);

            return $path;
        }

        $optimizedPath = ImageOptimizer::store($disk->get($path), $kind);

        if ($optimizedPath === null) {
            $this->warn("No se pudo optimizar {$path}.");

            return $path;
        }

        $this->convertedImages++;

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
