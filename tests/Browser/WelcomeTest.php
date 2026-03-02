<?php

declare(strict_types=1);

it('has welcome page', function (): void {
    $manifest = base_path('public/build/manifest.json');
    if (! file_exists($manifest)) {
        test()->markTestSkipped('Vite build missing. Run: npm run build');
    }

    $page = visit('/');

    $page->assertSee(config('app.name'));
});
