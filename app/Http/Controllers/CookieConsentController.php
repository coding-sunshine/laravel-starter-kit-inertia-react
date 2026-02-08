<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

final class CookieConsentController
{
    /**
     * Set the cookie consent cookie and redirect back.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $name = config('cookie-consent.cookie_name', 'laravel_cookie_consent');
        $lifetimeDays = (int) config('cookie-consent.cookie_lifetime', 365 * 20);
        $minutes = $lifetimeDays * 24 * 60;

        Cookie::queue(
            $name,
            '1',
            $minutes,
            '/',
            config('session.domain'),
            config('session.secure'),
            true,
            false,
            config('session.same_site', 'lax')
        );

        return redirect()->back();
    }
}
