<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\UnlockAccount;
use App\Filament\Pages\Dashboard;
use App\Support\Favicon;
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
     * Sage from the storefront buttons in the light shades, the logo's emerald in the dark ones.
     *
     * @var array<int, string>
     */
    private const array PRIMARY = [
        50 => '#eef3ee',
        100 => '#dde8de',
        200 => '#c2d6c4',
        300 => '#a6c4a9',
        400 => '#95b998',
        500 => '#4f8a6c',
        600 => '#2e6b55',
        700 => '#1f5c45',
        800 => '#194a38',
        900 => '#133a2c',
        950 => '#0b241b',
    ];

    /**
     * Grays that run from the storefront's cream paper to deep forest green, so light mode
     * feels like the store and dark mode stays green instead of neutral.
     *
     * @var array<int, string>
     */
    private const array GRAY = [
        50 => '#f8f6f2',
        100 => '#f0ece5',
        200 => '#e3ddd3',
        300 => '#cdc6ba',
        400 => '#a19c92',
        500 => '#7a776f',
        600 => '#5b5f59',
        700 => '#3f4743',
        800 => '#28312d',
        900 => '#19221e',
        950 => '#0f1714',
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
            ->favicon(fn (): string => Favicon::url(32))
            ->font('Albert Sans')
            ->serifFont('Playfair Display')
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
