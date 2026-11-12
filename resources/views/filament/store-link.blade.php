{{--
    Opens the storefront from any manager page. A new tab keeps the manager, and any form
    being filled in, right where the owner left it. On a phone only the icon shows.
--}}
<x-filament::button
    tag="a"
    :href="route('home')"
    target="_blank"
    rel="noopener"
    color="gray"
    outlined
    size="sm"
    :icon="\Filament\Support\Icons\Heroicon::OutlinedBuildingStorefront"
    labeled-from="sm"
    tooltip="Abre tu tienda en otra pestaña"
>
    Ver tienda
</x-filament::button>
