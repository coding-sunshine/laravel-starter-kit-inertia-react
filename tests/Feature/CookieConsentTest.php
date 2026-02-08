<?php

declare(strict_types=1);

it('redirects back and sets cookie when accepting cookie consent', function (): void {
    $cookieName = config('cookie-consent.cookie_name', 'laravel_cookie_consent');

    $response = $this->get(route('cookie-consent.accept'));

    $response->assertRedirect();
    $response->assertCookie($cookieName);
});
