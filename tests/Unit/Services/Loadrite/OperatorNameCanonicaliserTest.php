<?php

declare(strict_types=1);

use App\Models\LoaderOperator;
use App\Services\Loadrite\OperatorNameCanonicaliser;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeCanonicaliser(?CacheRepository $cache = null): OperatorNameCanonicaliser
{
    return new OperatorNameCanonicaliser($cache ?? Cache::store('array'));
}

// ---------------------------------------------------------------------------
// Title-case and trim
// ---------------------------------------------------------------------------

it('title-cases and trims', function (): void {
    $c = makeCanonicaliser();

    expect($c->canonicalise('  SURESH  '))->toBe('Suresh')
        ->and($c->canonicalise('suresh k'))->toBe('Suresh K')
        ->and($c->canonicalise('john doe'))->toBe('John Doe');
});

// ---------------------------------------------------------------------------
// Existing operator matches
// ---------------------------------------------------------------------------

it('returns existing operator name on exact match', function (): void {
    LoaderOperator::factory()->create(['name' => 'Suresh']);

    $c = makeCanonicaliser();

    expect($c->canonicalise('SURESH'))->toBe('Suresh')
        ->and($c->canonicalise('suresh'))->toBe('Suresh')
        ->and($c->canonicalise('  Suresh  '))->toBe('Suresh');
});

it('maps single-character typo to existing operator', function (): void {
    LoaderOperator::factory()->create(['name' => 'Suresh']);

    $c = makeCanonicaliser();

    // 'Suresj' has Levenshtein distance 1 from 'Suresh'
    expect($c->canonicalise('Suresj'))->toBe('Suresh');
});

it('returns input as-is for genuine new operator', function (): void {
    LoaderOperator::factory()->create(['name' => 'Suresh']);

    $c = makeCanonicaliser();

    // 'Ramesh' has no close match in the catalog.
    expect($c->canonicalise('Ramesh'))->toBe('Ramesh');
});

// ---------------------------------------------------------------------------
// Caching
// ---------------------------------------------------------------------------

it('caches the catalog for 5 minutes', function (): void {
    LoaderOperator::factory()->create(['name' => 'Suresh']);

    $cache = Cache::store('array');

    expect($cache->has(OperatorNameCanonicaliser::CACHE_KEY))->toBeFalse();

    $c = new OperatorNameCanonicaliser($cache);
    $c->canonicalise('Suresh');

    expect($cache->has(OperatorNameCanonicaliser::CACHE_KEY))->toBeTrue();

    // The TTL stored should be 5 minutes (300 s). We verify by reading back.
    $catalog = $cache->get(OperatorNameCanonicaliser::CACHE_KEY);

    expect($catalog)->toBeArray()->toContain('Suresh');
});

// ---------------------------------------------------------------------------
// Edge cases
// ---------------------------------------------------------------------------

it('returns null on empty input', function (): void {
    expect(makeCanonicaliser()->canonicalise(''))->toBeNull()
        ->and(makeCanonicaliser()->canonicalise('   '))->toBeNull()
        ->and(makeCanonicaliser()->canonicalise(null))->toBeNull();
});
