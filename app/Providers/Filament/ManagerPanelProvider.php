<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\UnlockAccount;
use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ManagerPanelProvider extends PanelProvider
{
    /**
     * The storefront's rose in the light shades and its burgundy in the dark ones, so buttons,
     * links and the active menu item wear the same wine color as the store.
     *
     * @var array<int, string>
     */
    private const array PRIMARY = [
        50 => '#fbf3f3',
        100 => '#f2dfe0',
        200 => '#e8c6c8',
        300 => '#d8a3a6',
        400 => '#b9787f',
        500 => '#8e3a4a',
        600 => '#6a1e2e',
        700 => '#501522',
        800 => '#40111c',
        900 => '#310d15',
        950 => '#1f080d',
    ];

    /**
     * Grays that run from the storefront's ivory paper through its taupe to its warm ink, so
     * light mode feels like the store and dark mode stays warm instead of neutral.
     *
     * @var array<int, string>
     */
    private const array GRAY = [
        50 => '#f6efe7',
        100 => '#eadfd6',
        200 => '#ddcfc3',
        300 => '#c8b6a8',
        400 => '#a39185',
        500 => '#7d6d66',
        600 => '#5d4f4c',
        700 => '#443838',
        800 => '#2b2022',
        900 => '#1f1718',
        950 => '#150f10',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('manager')
            ->path('manager')
            ->viteTheme('resources/css/filament/manager/theme.css')
            ->login(Login::class)
            ->routes(fn () => Route::get('desbloquear', UnlockAccount::class)->name('auth.unlock'))
            ->profile()
            // After creating something, go back to its list instead of opening it to edit.
            ->resourceCreatePageRedirect('index')
            ->brandName('Hade Manager')
            ->brandLogo(fn (): View => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/logos/favicon-vino-32.png').'?v=2')
            ->font('Montserrat')
            ->serifFont('Cormorant Garamond')
            ->colors([
                'primary' => self::PRIMARY,
                'gray' => self::GRAY,
            ])
            ->renderHook(
                PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn (): View => view('filament.simple-layout-band'),
            )
            ->navigationGroups([
                'Catálogo',
                'Tienda online',
                'Atención al cliente',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
