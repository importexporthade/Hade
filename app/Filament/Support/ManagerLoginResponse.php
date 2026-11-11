<?php

namespace App\Filament\Support;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

/**
 * Where the owner lands after signing in: the page she was trying to open, or the manager's
 * home. When her session ran out in the middle of a save or an upload, Laravel remembered
 * Livewire's own address (/livewire-…/update), which only takes requests from a page, and
 * signing in opened it and showed "405 Method Not Allowed"; such an address leads home instead.
 */
class ManagerLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        return redirect()->to(self::destination());
    }

    /**
     * The remembered page if it is one that can be opened, otherwise the manager's home.
     */
    public static function destination(): string
    {
        $intended = session()->pull('url.intended');

        if (! is_string($intended) || self::isLivewireAddress($intended)) {
            return Filament::getUrl();
        }

        return $intended;
    }

    private static function isLivewireAddress(string $url): bool
    {
        return (bool) preg_match('#^/livewire(-[^/]+)?/#i', (string) parse_url($url, PHP_URL_PATH));
    }
}
