<?php

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum HomeSectionType: string implements HasDescription, HasLabel
{
    case Offers = 'offers';
    case Featured = 'featured';
    case Latest = 'latest';
    case Category = 'category';
    case Categories = 'categories';

    public function getLabel(): string
    {
        return match ($this) {
            self::Offers => 'Ofertas',
            self::Featured => 'Productos destacados',
            self::Latest => 'Novedades',
            self::Category => 'Productos de una categoría',
            self::Categories => 'Grilla de categorías',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Offers => 'Productos con precio anterior tachado.',
            self::Featured => 'Productos marcados como destacados.',
            self::Latest => 'Los últimos productos agregados.',
            self::Category => 'Productos de la categoría que elijas.',
            self::Categories => 'Las categorías visibles con su foto.',
        };
    }

    public function showsProducts(): bool
    {
        return $this !== self::Categories;
    }
}
