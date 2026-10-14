<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns photos into light WebP files on the public disk: scaled down to a maximum size,
 * turned upright when the camera stored them sideways, and optionally with a small
 * thumbnail beside them for product tiles.
 */
class ImageOptimizer
{
    public const int QUALITY = 82;

    /**
     * Longest side, in pixels, of each kind of photo the store keeps.
     */
    public const int PRODUCT_SIZE = 1200;

    public const int PRODUCT_THUMBNAIL_SIZE = 480;

    public const int CATEGORY_SIZE = 800;

    public const int BANNER_SIZE = 1920;

    public const int MOBILE_BANNER_SIZE = 1080;

    public const int LOGO_SIZE = 600;

    public const int HEADER_SIZE = 2000;

    /**
     * Saves the image as optimized WebP in the directory and returns its path, or null when
     * the contents are not an image GD can read.
     */
    public static function store(string $contents, string $directory, int $maxSize, ?int $thumbnailSize = null): ?string
    {
        $image = self::decode($contents);

        if ($image === null) {
            return null;
        }

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';

        self::write($image, $path, $maxSize);

        if ($thumbnailSize !== null) {
            self::write($image, self::thumbnailPath($path), $thumbnailSize);
        }

        return $path;
    }

    /**
     * Where the thumbnail of a photo lives: a "thumbs" folder next to it, same file name.
     */
    public static function thumbnailPath(string $path): string
    {
        $directory = dirname($path);

        return ($directory === '.' ? '' : $directory.'/').'thumbs/'.basename($path);
    }

    public static function isOptimized(string $path): bool
    {
        return str_ends_with(strtolower($path), '.webp');
    }

    private static function decode(string $contents): ?GdImage
    {
        self::allowRoomForLargePhotos();

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        imagepalettetotruecolor($image);

        return self::upright($image, $contents);
    }

    /**
     * Phones save photos sideways and note the turn in the EXIF data; GD ignores it.
     */
    private static function upright(GdImage $image, string $contents): GdImage
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($contents, "\xFF\xD8")) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));

        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => 270,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    /**
     * A 12-megapixel phone photo takes about 48 MB once decoded, more than a default
     * 128 MB limit leaves room for, so the limit is raised for this request when lower.
     */
    private static function allowRoomForLargePhotos(): void
    {
        $limit = ini_get('memory_limit');

        if ($limit !== '-1' && ini_parse_quantity($limit) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }

    private static function write(GdImage $image, string $path, int $maxSize): void
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSize / max($width, $height));

        $resized = $scale < 1
            ? imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC)
            : $image;

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        ob_start();
        imagewebp($resized, null, self::QUALITY);

        Storage::disk('public')->put($path, (string) ob_get_clean());
    }
}
