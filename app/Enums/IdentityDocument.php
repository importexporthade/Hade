<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IdentityDocument: string implements HasLabel
{
    case Dni = 'dni';
    case ForeignerCard = 'ce';
    case Passport = 'pasaporte';
    case Ruc = 'ruc';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dni => 'DNI',
            self::ForeignerCard => 'Carné de extranjería',
            self::Passport => 'Pasaporte',
            self::Ruc => 'RUC',
        };
    }

    /**
     * Validation pattern for the number of each document.
     */
    public function pattern(): string
    {
        return match ($this) {
            self::Dni => '/^\d{8}$/',
            self::Ruc => '/^\d{11}$/',
            self::ForeignerCard, self::Passport => '/^[A-Za-z0-9]{6,12}$/',
        };
    }
}
