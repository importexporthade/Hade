<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/**
 * The two kinds of entry the consumer protection rules define for the complaints book.
 */
enum ComplaintType: string implements HasColor, HasDescription, HasLabel
{
    case Claim = 'reclamo';
    case Grievance = 'queja';

    public function getLabel(): string
    {
        return match ($this) {
            self::Claim => 'Reclamo',
            self::Grievance => 'Queja',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Claim => 'Disconformidad con los productos o servicios adquiridos.',
            self::Grievance => 'Malestar o descontento con la atención al público, sin relación con el producto o servicio.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Claim => 'warning',
            self::Grievance => 'info',
        };
    }
}
