<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives each store visitor an anonymous, random id kept in a cookie, so the metrics can
 * tell many visits from one person apart from visits by many people. It holds no personal
 * data and is not linked to any account.
 */
class IdentifyVisitor
{
    public const string COOKIE = 'hade_visitor';

    public const string ATTRIBUTE = 'visitor_id';

    private const int ONE_YEAR_IN_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        $visitorId = $request->cookie(self::COOKIE);

        if (! is_string($visitorId) || ! Str::isUuid($visitorId)) {
            $visitorId = (string) Str::uuid();
            Cookie::queue(self::COOKIE, $visitorId, self::ONE_YEAR_IN_MINUTES);
        }

        $request->attributes->set(self::ATTRIBUTE, $visitorId);

        return $next($request);
    }
}
