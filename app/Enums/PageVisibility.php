<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * Where an informational page shows up in the store, as one choice instead of two switches
 * that could be combined into states that made no sense (in the footer but unpublished).
 */
enum PageVisibility: string implements HasColor, HasDescription, HasLabel
{
    case Hidden = 'hidden';
    case Menu = 'menu';
    case MenuAndFooter = 'menu_and_footer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hidden => 'Oculta',
            self::Menu => 'En el menú',
            self::MenuAndFooter => 'En el menú y en el pie',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Hidden => 'Nadie la ve en la tienda. Útil mientras la escribes.',
            self::Menu => 'Aparece en el menú "Información".',
            self::MenuAndFooter => 'Aparece en el menú "Información" y en el pie de página.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Hidden => 'gray',
            self::Menu => 'info',
            self::MenuAndFooter => 'success',
        };
    }

    public function isPublished(): bool
    {
        return $this !== self::Hidden;
    }
}
