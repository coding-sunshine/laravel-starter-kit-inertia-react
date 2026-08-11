<?php

declare(strict_types=1);

use App\Services\Loadrite\WagonTypeNormaliser;

// ---------------------------------------------------------------------------
// Helper: build a normaliser with a pinned wagon_cc catalog so tests are
// deterministic regardless of future config changes.
// When $catalog is provided, type_abbreviations is also cleared so the only
// canonical entries are the supplied ones.
// ---------------------------------------------------------------------------

function makeNormaliser(array $catalog = []): WagonTypeNormaliser
{
    if ($catalog !== []) {
        $wagonCc = [];
        foreach ($catalog as $type) {
            $wagonCc[$type] = ['cc' => 68.0, 'tare' => 22.0];
        }
        config([
            'loadrite.wagon_cc' => $wagonCc,
            'loadrite.type_abbreviations' => [], // isolate catalog to supplied list
        ]);
    }

    return new WagonTypeNormaliser;
}

// ---------------------------------------------------------------------------
// Exact match tests
// ---------------------------------------------------------------------------

it('normalises exact catalog match', function (): void {
    makeNormaliser(['BOXNHL', 'BOBRNHSM1', 'BOXNS']);

    expect((new WagonTypeNormaliser)->normalise('BOXNHL'))->toBe('BOXNHL');
});

it('normalises lowercase', function (): void {
    makeNormaliser(['BOXNHL', 'BOBRNHSM1']);

    expect((new WagonTypeNormaliser)->normalise('boxnhl'))->toBe('BOXNHL');
});

it('strips separators and whitespace', function (): void {
    // HL-2D strips to HL2D which is a type_abbreviations key → canonical 'HL2D'.
    // BOBR NHSM1 strips to BOBRNHSM1 which is a wagon_cc key → canonical 'BOBRNHSM1'.
    // Use the real config (no override) so both catalogs are available.
    config(['loadrite.type_abbreviations' => config('loadrite.type_abbreviations', [])]);

    expect((new WagonTypeNormaliser)->normalise('HL-2D'))->toBe('HL2D')
        ->and((new WagonTypeNormaliser)->normalise('BOBR NHSM1'))->toBe('BOBRNHSM1');
});

it('strips hyphens and spaces to match catalog', function (): void {
    // BOXNHL2D in catalog; operator keyed BOX-NHL2D or BOXNHL 2D
    makeNormaliser(['BOXNHL2D', 'BOBRNHSM1']);

    expect((new WagonTypeNormaliser)->normalise('BOXN-HL2D'))->toBe('BOXNHL2D')
        ->and((new WagonTypeNormaliser)->normalise('BOXNHL 2D'))->toBe('BOXNHL2D');
});

it('strips separators from multi-word type like BOBRNHSM1', function (): void {
    makeNormaliser(['BOBRNHSM1', 'BOXNHL']);

    expect((new WagonTypeNormaliser)->normalise('BOBR NHSM1'))->toBe('BOBRNHSM1')
        ->and((new WagonTypeNormaliser)->normalise('BOBRN-HSM1'))->toBe('BOBRNHSM1');
});

// ---------------------------------------------------------------------------
// Levenshtein tests
// ---------------------------------------------------------------------------

it('accepts levenshtein distance 1', function (): void {
    // BOXNHL is in catalog; BOXNHKL has distance 1 (inserted K)
    makeNormaliser(['BOXNHL', 'BOBRN']);

    expect((new WagonTypeNormaliser)->normalise('BOXNHKL'))->toBe('BOXNHL');
});

// ---------------------------------------------------------------------------
// Null / garbage tests
// ---------------------------------------------------------------------------

it('returns null on garbage input', function (): void {
    makeNormaliser(['BOXNHL', 'BOBRNHSM1', 'BOXNS']);

    expect((new WagonTypeNormaliser)->normalise('???'))->toBeNull()
        ->and((new WagonTypeNormaliser)->normalise('XYZ'))->toBeNull();
});

it('returns null on empty string', function (): void {
    makeNormaliser(['BOXNHL']);

    expect((new WagonTypeNormaliser)->normalise(''))->toBeNull();
});

it('returns null on whitespace only', function (): void {
    makeNormaliser(['BOXNHL']);

    expect((new WagonTypeNormaliser)->normalise('   '))->toBeNull();
});

it('returns null on null input', function (): void {
    makeNormaliser(['BOXNHL']);

    expect((new WagonTypeNormaliser)->normalise(null))->toBeNull();
});

it('returns null when two catalog entries tie on levenshtein distance', function (): void {
    // ABCDE and ABCDF both have distance 1 from ABCDE (wait — they're identical to ABCDE)
    // Let's use: catalog has ABCDE and ABCDF, input is ABCDX (dist 1 to both)
    makeNormaliser(['ABCDE', 'ABCDF']);

    expect((new WagonTypeNormaliser)->normalise('ABCDX'))->toBeNull();
});

it('drops the carrying-capacity suffix operators key alongside the type', function (): void {
    makeNormaliser(['BOBRNHSM1', 'BOXNS', 'BOXNLW']);

    expect((new WagonTypeNormaliser)->normalise('BOBRNHSM1 65T'))->toBe('BOBRNHSM1')
        ->and((new WagonTypeNormaliser)->normalise('BOXNS 70.70T'))->toBe('BOXNS')
        ->and((new WagonTypeNormaliser)->normalise('BOXNLW 70 T'))->toBe('BOXNLW');
});

it('keeps a capacity token that is part of the type itself', function (): void {
    makeNormaliser(['BOXNHL', 'BOXNHL25T']);

    expect((new WagonTypeNormaliser)->normalise('BOXNHL25T'))->toBe('BOXNHL25T')
        ->and((new WagonTypeNormaliser)->normalise('BOXNHL25T 70T'))->toBe('BOXNHL25T');
});

it('does not fuzzy-match letterless or very short operator mis-keys', function (): void {
    makeNormaliser(['BOXNG', 'G']);

    expect((new WagonTypeNormaliser)->normalise('0'))->toBeNull()
        ->and((new WagonTypeNormaliser)->normalise('11342'))->toBeNull()
        ->and((new WagonTypeNormaliser)->normalise('G'))->toBe('G');
});
