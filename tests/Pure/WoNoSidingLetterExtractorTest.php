<?php

declare(strict_types=1);

use App\Support\WoNoSidingLetterExtractor;

it('extracts letters from two-field transport patterns', function (): void {
    $x = new WoNoSidingLetterExtractor;

    expect($x->extractFromTwoFields('D1', null))->toBe('D');
    expect($x->extractFromTwoFields(null, 'REF/WO-P12'))->toBe('P');
    expect($x->extractFromTwoFields(null, 'K9'))->toBe('K');
});

it('extracts from a single WO NO cell including first-character fallback', function (): void {
    $x = new WoNoSidingLetterExtractor;

    expect($x->extractFromWoNo('P99'))->toBe('P');
    expect($x->extractFromWoNo('AREA/WO-K12'))->toBe('K');
    expect($x->extractFromWoNo(' D — pending '))->toBe('D');
    expect($x->extractFromWoNo(null))->toBeNull()
        ->and($x->extractFromWoNo('XYZ'))->toBeNull();
});
