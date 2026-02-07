<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('all security headers are present on web responses', function (): void {
    $response = get('/');

    $response->assertSuccessful();
    $response->assertHeader('Content-Security-Policy');
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
});

test('all security headers are present on API responses', function (): void {
    Role::query()->firstOrCreate(['name' => 'user']);
    $user = User::factory()->create();

    $response = actingAs($user, 'sanctum')->get('/api');

    $response->assertSuccessful();
    $response->assertHeader('Content-Security-Policy');
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
});

test('CSP header includes required directives', function (): void {
    $response = get('/');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain('script-src')
        ->toContain("'self'")
        ->toContain('style-src')
        ->toContain("'unsafe-inline'")
        ->toContain('img-src')
        ->toContain('data:')
        ->toContain('blob:')
        ->toContain('font-src')
        ->toContain('connect-src');
});

test('CSP includes unsafe-inline for scripts when nonces disabled', function (): void {
    $response = get('/');

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toContain("'unsafe-inline'");
});

test('HSTS header enforces HTTPS for one year', function (): void {
    $response = get('/');

    $hsts = $response->headers->get('Strict-Transport-Security');

    expect($hsts)
        ->toContain('max-age=31536000')
        ->toContain('includeSubDomains');
});

test('X-Frame-Options prevents clickjacking', function (): void {
    $response = get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});

test('security headers are present on authenticated web routes', function (): void {
    Role::query()->firstOrCreate(['name' => 'user']);
    $user = User::factory()->create();

    $response = actingAs($user)->get(route('dashboard'));

    $response->assertHeader('Content-Security-Policy');
    $response->assertHeader('Strict-Transport-Security');
    $response->assertHeader('X-Frame-Options');
    $response->assertHeader('X-Content-Type-Options');
    $response->assertHeader('Referrer-Policy');
    $response->assertHeader('Permissions-Policy');
});
