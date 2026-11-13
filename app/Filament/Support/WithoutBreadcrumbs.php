<?php

namespace App\Filament\Support;

/**
 * The tables are the first screen of their section, so there is nowhere to go back to and
 * the breadcrumbs would only repeat the heading. They appear from create and edit onwards.
 */
trait WithoutBreadcrumbs
{
    /**
     * @return array<string|int, string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }
}
