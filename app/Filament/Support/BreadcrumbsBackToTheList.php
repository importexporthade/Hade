<?php

namespace App\Filament\Support;

/**
 * Breadcrumbs of two steps, the table and the current screen: "Productos › Editar".
 * Filament puts the record's name in between as a link to the very page it is on, and
 * leaves the screens that list and edit in one place with an empty step.
 */
trait BreadcrumbsBackToTheList
{
    /**
     * @return array<string|int, string>
     */
    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();

        return [
            $resource::getUrl() => $resource::getBreadcrumb(),
            $this->getBreadcrumb() ?? __('filament-panels::resources/pages/list-records.breadcrumb'),
        ];
    }
}
