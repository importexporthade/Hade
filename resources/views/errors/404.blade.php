@php
    $storeName = rescue(fn () => \App\Models\StoreSetting::current()->store_name, config('app.name'), report: false);
@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>Página no encontrada - {{ $storeName }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logos/favicon-vino-32.png') }}?v=2" sizes="32x32">
        <link rel="icon" type="image/png" href="{{ asset('images/logos/favicon-vino-192.png') }}?v=2" sizes="192x192">
        <link rel="apple-touch-icon" href="{{ asset('images/logos/favicon-vino-180.png') }}?v=2">
        <meta name="theme-color" content="#6A1E2E">

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="flex min-h-dvh flex-col overflow-x-clip bg-cream text-ink">
        <main class="store-container flex flex-1 flex-col items-center justify-center py-12 text-center sm:py-16">
            <img src="{{ asset('images/errors/404.webp') }}" alt="" width="1400" height="521" class="h-auto w-full max-w-lg sm:max-w-2xl">

            <h1 class="mt-6 font-serif text-[clamp(2.25rem,1.5rem+3vw,3.75rem)] leading-tight font-medium tracking-[-0.03em] text-burgundy">Ups... esta página se perdió</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-ink/70 sm:text-base">
                Parece que el enlace que intentas abrir ya no existe o fue movido.
                Pero no te preocupes, ¡todavía hay muchas cosas lindas por descubrir en {{ $storeName }}!
            </p>

            <a href="{{ route('home') }}" class="group mt-8 inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-burgundy px-8 text-sm font-semibold text-white transition duration-300 hover:bg-burgundy-dark focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-burgundy">
                Volver al inicio
                <x-store.icon name="chevron-right" class="size-4 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transition-none motion-reduce:group-hover:translate-x-0" />
            </a>
        </main>

        <div class="scalloped-section h-12 bg-burgundy [--scallop-color:var(--color-burgundy)] after:hidden" aria-hidden="true"></div>
    </body>
</html>
