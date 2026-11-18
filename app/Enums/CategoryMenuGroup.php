<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The group of the "Tienda" menu a category is listed under.
 */
enum CategoryMenuGroup: string implements HasLabel
{
    case Nails = 'nails';
    case Complements = 'complements';
    case Discover = 'discover';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nails => 'Para tus uñas',
            self::Complements => 'Complementos',
            self::Discover => 'Para descubrir',
        };
    }
}
