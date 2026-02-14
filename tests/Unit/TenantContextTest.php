<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Services\TenantContext;

beforeEach(function (): void {
    TenantContext::flush();
});

it('returns null when no context set', function (): void {
    expect(TenantContext::id())->toBeNull()
        ->and(TenantContext::get())->toBeNull()
        ->and(TenantContext::check())->toBeFalse();
});

it('stores and returns organization context', function (): void {
    $org = Organization::factory()->create();

    TenantContext::set($org);

    expect(TenantContext::check())->toBeTrue()
        ->and(TenantContext::id())->toBe($org->id)
        ->and(TenantContext::get())->toBe($org)
        ->and(TenantContext::organization())->toBe($org);
});

it('forgets context after forget', function (): void {
    $org = Organization::factory()->create();
    TenantContext::set($org);

    TenantContext::forget();

    expect(TenantContext::check())->toBeFalse()
        ->and(TenantContext::get())->toBeNull();
});

it('flush clears context', function (): void {
    $org = Organization::factory()->create();
    TenantContext::set($org);

    TenantContext::flush();

    expect(TenantContext::get())->toBeNull();
});
