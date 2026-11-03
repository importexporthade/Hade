<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum HomeSectionType: string implements HasDescription, HasLabel
{
    case Offers = 'offers';
    case Latest = 'latest';
    case Category = 'category';

    public function getLabel(): string
    {
        return match ($this) {
            self::Offers => 'Ofertas',
            self::Latest => 'Novedades',
            self::Category => 'Productos de una categoría',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Offers => 'Productos con precio anterior tachado.',
            self::Latest => 'Los productos que marcaste como novedad, del más nuevo al más antiguo.',
            self::Category => 'Productos de la categoría que elijas.',
        };
    }
}
