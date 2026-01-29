<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('serves favicon when file exists', function (): void {
    /** @var Tests\TestCase $test */
    $test = $this;
    $path = public_path('favicon.ico');

    if (! File::exists($path)) {
        $test->markTestSkipped('public/favicon.ico does not exist');
    }

    $response = $test->get('/favicon.ico');

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/x-icon');
});

it('redirects to favicon.svg when favicon.ico is missing', function (): void {
    /** @var Tests\TestCase $test */
    $test = $this;
    $path = public_path('favicon.ico');

    if (File::exists($path)) {
        $test->markTestSkipped('public/favicon.ico exists; cannot test fallback');
    }

    $response = $test->get('/favicon.ico');

    $response->assertRedirect('/favicon.svg');
});
