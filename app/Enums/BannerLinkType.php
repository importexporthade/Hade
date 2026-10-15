<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Where a banner takes the customer when she taps it, chosen in plain words so the owner
 * never has to type a web address.
 */
enum BannerLinkType: string implements HasDescription, HasLabel
{
    case None = 'none';
    case Product = 'product';
    case Category = 'category';
    case AllProducts = 'all_products';
    case Page = 'page';
    case WhatsApp = 'whatsapp';
    case External = 'external';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'A ningún lado',
            self::Product => 'A un producto',
            self::Category => 'A una categoría',
            self::AllProducts => 'A todos los productos',
            self::Page => 'A una página informativa',
            self::WhatsApp => 'Al WhatsApp de la tienda',
            self::External => 'A otra página web',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::None => 'El banner solo se muestra; no se puede tocar.',
            self::Product => 'Eliges el producto de una lista.',
            self::Category => 'Por ejemplo, todos los Kits y Promociones.',
            self::AllProducts => 'El catálogo completo de la tienda.',
            self::Page => 'Por ejemplo, Envíos y delivery.',
            self::WhatsApp => 'Abre un chat con el número de la tienda.',
            self::External => 'Instagram, TikTok u otro sitio. Pegas su dirección.',
        };
    }

    /**
     * Destinations outside the store open in a new tab so the store stays open behind them.
     */
    public function opensOutsideTheStore(): bool
    {
        return in_array($this, [self::WhatsApp, self::External], true);
    }
}
