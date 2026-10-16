<?php

namespace App\Enums;

/**
 * Every kind of photo the store keeps, with the largest size it is saved at and the
 * smaller copies made from it so each screen downloads only what it shows.
 */
enum ImageKind: string
{
    case Product = 'product';
    case ProductCover = 'product_cover';
    case Category = 'category';
    case Banner = 'banner';
    case MobileBanner = 'mobile_banner';
    case Logo = 'logo';
    case Header = 'header';

    /**
     * Longest side, in pixels, of the main copy.
     */
    public function maxSize(): int
    {
        return match ($this) {
            self::Product => 1200,
            self::ProductCover, self::Category => 800,
            self::Banner => 1920,
            self::MobileBanner => 1080,
            self::Logo => 600,
            self::Header => 2000,
        };
    }

    /**
     * Widths of the smaller copies, smallest first.
     *
     * @return list<int>
     */
    public function widths(): array
    {
        return match ($this) {
            self::Product => [320, 480, 800],
            self::ProductCover, self::Category => [320, 480],
            self::Banner => [640, 1080, 1440],
            self::MobileBanner => [480, 720],
            self::Logo => [96, 192],
            self::Header => [800, 1200],
        };
    }

    public function directory(): string
    {
        return match ($this) {
            self::Product, self::ProductCover => 'products',
            self::Category => 'categories',
            self::Banner, self::MobileBanner => 'banners',
            self::Logo, self::Header => 'store',
        };
    }

    /**
     * Every copy width any kind uses, so deleting a photo can remove all its copies
     * without knowing which kind it was.
     *
     * @return list<int>
     */
    public static function allWidths(): array
    {
        return collect(self::cases())
            ->flatMap(fn (self $kind): array => $kind->widths())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
