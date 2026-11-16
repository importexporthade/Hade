<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageFooterSection: string implements HasLabel
{
    case Information = 'information';
    case Legal = 'legal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Information => 'Información',
            self::Legal => 'Legal',
        };
    }
}
