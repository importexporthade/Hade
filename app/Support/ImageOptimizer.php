<?php

namespace App\Support;

use App\Enums\ImageKind;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns photos into light WebP files on the public disk: the main copy scaled down to its
 * kind's maximum size, turned upright when the camera stored it sideways, and smaller
 * copies beside it ("abc-480.webp") so every screen downloads only the size it shows.
 */
class ImageOptimizer
{
    public const int QUALITY = 82;

    /** Longest side, in pixels, of the JPEG copy shared links show. */
    public const int SHARE_SIZE = 1200;

    /**
     * Saves the image and its smaller copies, and returns the main copy's path, or null
     * when the contents are not an image GD can read or GD could not make the copies; the
     * caller then keeps the photo as it was uploaded instead.
     */
    public static function store(string $contents, ImageKind $kind): ?string
    {
        $image = self::decode($contents);

        if ($image === null) {
            return null;
        }

        $path = $kind->directory().'/'.Str::uuid().'.webp';

        if (! self::write($image, $path, longestSide: $kind->maxSize()) || ! self::writeVariants($image, $path, $kind)) {
            Storage::disk('public')->delete([$path, ...self::companionPaths($path)]);

            return null;
        }

        return $path;
    }

    /**
     * Makes the smaller copies a photo is missing, reading them from its main copy.
     * Returns false when the photo is not an optimized one that can be read.
     */
    public static function addMissingVariants(string $path, ImageKind $kind): bool
    {
        $disk = Storage::disk('public');

        if (! self::isOptimized($path) || ! $disk->exists($path)) {
            return false;
        }

        $missing = array_filter($kind->widths(), fn (int $width): bool => ! $disk->exists(self::variantPath($path, $width)));

        if ($missing === []) {
            return true;
        }

        $image = self::decode($disk->get($path));

        if ($image === null) {
            return false;
        }

        foreach ($missing as $width) {
            if (! self::write($image, self::variantPath($path, $width), width: $width)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Where the copy of a given width lives: "products/abc.webp" → "products/abc-480.webp".
     */
    public static function variantPath(string $path, int $width): string
    {
        return preg_replace('/\.webp$/i', "-{$width}.webp", $path) ?? $path;
    }

    /**
     * Where the copy for shared links lives: "products/abc.webp" → "products/abc-share.jpg".
     */
    public static function sharePath(string $path): string
    {
        return preg_replace('/\.webp$/i', '-share.jpg', $path) ?? $path;
    }

    /**
     * Disk path of a JPEG copy of the photo for WhatsApp, Facebook and Instagram, which do
     * not reliably show WebP and drop pictures above about 300 KB. The copy is made the
     * first time a page is shared, so photos saved before it existed get one too. Returns
     * null when the photo is missing or cannot be read.
     */
    public static function shareCopy(string $path): ?string
    {
        $disk = Storage::disk('public');

        if (! self::isOptimized($path)) {
            $isSharable = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'], true);

            return $isSharable && $disk->exists($path) ? $path : null;
        }

        $sharePath = self::sharePath($path);

        if ($disk->exists($sharePath)) {
            return $sharePath;
        }

        if (! $disk->exists($path) || ($image = self::decode($disk->get($path))) === null) {
            return null;
        }

        return self::writeJpeg($image, $sharePath) ? $sharePath : null;
    }

    /**
     * Every file that may belong to a photo besides the photo itself: its copies of every
     * width, the copy for shared links, and the "thumbs" copy older versions of the store made.
     *
     * @return list<string>
     */
    public static function companionPaths(string $path): array
    {
        if (! self::isOptimized($path)) {
            return [];
        }

        $directory = dirname($path);
        $legacyThumbnail = ($directory === '.' ? '' : $directory.'/').'thumbs/'.basename($path);

        return [
            ...array_map(fn (int $width): string => self::variantPath($path, $width), ImageKind::allWidths()),
            self::sharePath($path),
            $legacyThumbnail,
        ];
    }

    public static function isOptimized(string $path): bool
    {
        return str_ends_with(strtolower($path), '.webp');
    }

    /**
     * Public URL of a photo, at the requested width when it has copies; photos saved before
     * the optimizer have none and are served as they are.
     */
    public static function url(string $path, ?int $width = null): string
    {
        return Storage::disk('public')->url(self::displayPath($path, $width));
    }

    /**
     * Disk path of the copy closest to the requested width, for places that take a path.
     */
    public static function displayPath(string $path, ?int $width = null): string
    {
        return $width !== null && self::isOptimized($path) ? self::variantPath($path, $width) : $path;
    }

    /**
     * The "srcset" list that lets the browser pick the copy that fits the screen, or null
     * for photos without copies.
     */
    public static function srcset(string $path, ImageKind $kind): ?string
    {
        if (! self::isOptimized($path)) {
            return null;
        }

        return collect($kind->widths())
            ->map(fn (int $width): string => self::url($path, $width)." {$width}w")
            ->push(self::url($path)." {$kind->maxSize()}w")
            ->implode(', ');
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

    private static function writeVariants(GdImage $image, string $path, ImageKind $kind): bool
    {
        foreach ($kind->widths() as $width) {
            if (! self::write($image, self::variantPath($path, $width), width: $width)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Saves a WebP copy no larger than the limit given: the longest side for main copies,
     * the width for smaller ones. Photos are never enlarged, so a copy of a small photo is
     * simply the photo itself and every listed copy exists. Returns false when GD could not
     * make the copy.
     */
    private static function write(GdImage $image, string $path, ?int $longestSide = null, ?int $width = null): bool
    {
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        $scale = min(1, $width !== null
            ? $width / $imageWidth
            : (int) $longestSide / max($imageWidth, $imageHeight));

        $resized = $scale < 1
            ? self::resize($image, max(1, (int) round($imageWidth * $scale)), max(1, (int) round($imageHeight * $scale)))
            : $image;

        if ($resized === null) {
            return false;
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        ob_start();
        $encoded = imagewebp($resized, null, self::QUALITY);
        $webp = (string) ob_get_clean();

        return $encoded && $webp !== '' && Storage::disk('public')->put($path, $webp);
    }

    /**
     * Saves a JPEG copy no longer than the share size on its longest side, with
     * transparent areas turned white since JPEG has no transparency.
     */
    private static function writeJpeg(GdImage $image, string $path): bool
    {
        $scale = min(1, self::SHARE_SIZE / max(imagesx($image), imagesy($image)));
        $width = max(1, (int) round(imagesx($image) * $scale));
        $height = max(1, (int) round(imagesy($image) * $scale));

        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            return false;
        }

        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

        if (! imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image))) {
            return false;
        }

        ob_start();
        $encoded = imagejpeg($canvas, null, self::QUALITY);
        $jpeg = (string) ob_get_clean();

        return $encoded && $jpeg !== '' && Storage::disk('public')->put($path, $jpeg);
    }

    /**
     * A smaller copy of the image, keeping its transparency. Hostinger's GD returned false
     * from imagescale() with bicubic scaling and saving any photo failed there; resampling
     * onto a new canvas works the same on every GD build.
     */
    private static function resize(GdImage $image, int $width, int $height): ?GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);

        if ($canvas === false) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        $copied = imagecopyresampled($canvas, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $copied ? $canvas : null;
    }
}
