<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductEventType: string implements HasLabel
{
    case View = 'view';
    case AddToCart = 'add_to_cart';
    case RemoveFromCart = 'remove_from_cart';
    case Checkout = 'checkout';

    public function getLabel(): string
    {
        return match ($this) {
            self::View => 'Vista',
            self::AddToCart => 'Agregado al carrito',
            self::RemoveFromCart => 'Quitado del carrito',
            self::Checkout => 'Comprado por WhatsApp',
        };
    }
}
