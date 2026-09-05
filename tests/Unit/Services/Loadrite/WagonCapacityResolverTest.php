<?php

declare(strict_types=1);

use App\Services\Loadrite\WagonCapacityResolver;

it('resolves CC from the official table by type abbreviation', function (string $abbr, float $cc): void {
    $r = (new WagonCapacityResolver)->resolve(null, $abbr);

    expect($r['cc'])->toBe($cc);
})->with([
    'HL → BOXNHL' => ['HL', 70.00],
    'NS → BOXNS (was wrongly 79 from fleet)' => ['NS', 70.70],
    'NR → BOXNR' => ['NR', 69.40],
    'RHS → BOXNRHS' => ['RHS', 69.40],
    'HA → BOXNHA' => ['HA', 67.00],
    'HSM1 → BOBRNHSM1' => ['HSM1', 65.00],
    'NM1 → BOBRNM1' => ['NM1', 65.00],
    'RM1 → BOBRM1' => ['RM1', 62.20],
]);

it('rejects junk type tokens — no CC, no false match', function (string $junk): void {
    $r = (new WagonCapacityResolver)->resolve(null, $junk);

    expect($r['cc'])->toBeNull()
        ->and($r['source'])->toBe('unresolved');
})->with([
    'bare number' => ['2'],
    'long number' => ['11342'],
    'single char' => ['X'],
]);

it('returns tare alongside CC from the official table', function (): void {
    $r = (new WagonCapacityResolver)->resolve(null, 'HL');

    expect($r['cc'])->toBe(70.00)
        ->and($r['tare'])->toBe(20.60)
        ->and($r['type'])->toBe('BOXNHL');
});

it('resolves the official capacity when handed a full catalog type', function (): void {
    config(['loadrite.wagon_cc' => ['BOBRNHSM1' => ['cc' => 65.00, 'tare' => 24.00]]]);

    $result = (new WagonCapacityResolver)->resolve(null, 'BOBRNHSM1');

    expect($result['cc'])->toBe(65.00)
        ->and($result['type'])->toBe('BOBRNHSM1')
        ->and($result['source'])->toBe('full-type');
});
