<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum PageVisibility: string implements HasColor, HasDescription, HasLabel
{
    case Hidden = 'hidden';
    case Menu = 'menu';
    case MenuAndFooter = 'menu_and_footer';
    case Footer = 'footer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hidden => 'Oculta',
            self::Menu => 'En el menú',
            self::MenuAndFooter => 'En el menú y en el pie',
            self::Footer => 'Solo en el pie',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Hidden => 'Nadie la ve en la tienda. Útil mientras la escribes.',
            self::Menu => 'Aparece en el menú "Información".',
            self::MenuAndFooter => 'Aparece en el menú "Información" y en el pie de página.',
            self::Footer => 'Aparece únicamente en el pie de página.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Hidden => 'gray',
            self::Menu => 'info',
            self::MenuAndFooter => 'success',
            self::Footer => 'info',
        };
    }

    public function isPublished(): bool
    {
        return $this !== self::Hidden;
    }

    public function isInMenu(): bool
    {
        return in_array($this, [self::Menu, self::MenuAndFooter], true);
    }

    public function isInFooter(): bool
    {
        return in_array($this, [self::Footer, self::MenuAndFooter], true);
    }
}
