<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ComplaintItemType: string implements HasLabel
{
    case Product = 'producto';
    case Service = 'servicio';

    public function getLabel(): string
    {
        return match ($this) {
            self::Product => 'Producto',
            self::Service => 'Servicio',
        };
    }
}
