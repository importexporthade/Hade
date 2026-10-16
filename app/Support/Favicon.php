<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Storage;

/**
 * The browser-tab icon, cut from the logo the owner uploaded. Browsers want small square
 * PNGs, so each size is made once from the current logo and kept next to it; a new logo
 * gets new files and the old ones are removed.
 */
class Favicon
{
    private const string DIRECTORY = 'store/favicons';

    public static function url(int $size): string
    {
        $logo = StoreSetting::current()->logo;
        $disk = Storage::disk('public');

        if (blank($logo) || ! $disk->exists($logo)) {
            return asset('images/favicon.svg');
        }

        $path = self::DIRECTORY.'/'.substr(md5($logo), 0, 12)."-{$size}.png";

        if (! $disk->exists($path)) {
            self::removeIconsOfOtherLogos($logo);

            if (! self::make($disk->get($logo), $path, $size)) {
                return asset('images/favicon.svg');
            }
        }

        return $disk->url($path);
    }

    private static function make(string $contents, string $path, int $size): bool
    {
        $logo = @imagecreatefromstring($contents);

        if ($logo === false) {
            return false;
        }

        $width = imagesx($logo);
        $height = imagesy($logo);
        $side = min($width, $height);

        $icon = imagecreatetruecolor($size, $size);
        imagealphablending($icon, false);
        imagesavealpha($icon, true);
        imagefill($icon, 0, 0, imagecolorallocatealpha($icon, 0, 0, 0, 127));
        imagecopyresampled($icon, $logo, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), $size, $size, $side, $side);

        ob_start();
        imagepng($icon, null, 9);

        return Storage::disk('public')->put($path, (string) ob_get_clean());
    }

    private static function removeIconsOfOtherLogos(string $logo): void
    {
        $disk = Storage::disk('public');
        $prefix = self::DIRECTORY.'/'.substr(md5($logo), 0, 12).'-';

        $disk->delete(array_filter(
            $disk->files(self::DIRECTORY),
            fn (string $file): bool => ! str_starts_with($file, $prefix),
        ));
    }
}
