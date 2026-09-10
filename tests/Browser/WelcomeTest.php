<?php

declare(strict_types=1);

it('redirects home to login', function (): void {
    $page = visit('/');

    $page->assertSee('Log in');
});
