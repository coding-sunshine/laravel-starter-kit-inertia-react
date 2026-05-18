<?php

declare(strict_types=1);

use App\Services\Loadrite\LoadriteUserDataParser;

function lrEvent(array $overrides = []): array
{
    return array_merge([
        'Id' => '1',
        'Event' => 'Short Total',
        'Weight' => '66.9',
        'Time' => '2026-05-16 11:33:43',
    ], $overrides);
}

it('parses scale 16 layout (UD1=rake, UD2=wagon, UD3=type)', function (): void {
    $parsed = (new LoadriteUserDataParser)->parse(lrEvent([
        'Scale ID' => '16',
        'Operator' => 'Pankaj',
        'UserData1' => '80',
        'UserData2' => '12001',
        'UserData3' => 'HL',
    ]));

    expect($parsed['rake_number'])->toBe('80')
        ->and($parsed['wagon_number'])->toBe('12001')
        ->and($parsed['wagon_type'])->toBe('HL')
        ->and($parsed['operator'])->toBe('Pankaj');
});

it('parses scale 11 and 13 with the same layout as 16', function (): void {
    foreach (['11', '13'] as $scale) {
        $parsed = (new LoadriteUserDataParser)->parse(lrEvent([
            'Scale ID' => $scale,
            'Operator' => 'Avishek',
            'UserData1' => '76',
            'UserData2' => '64209',
            'UserData3' => 'HSM1',
        ]));

        expect($parsed['rake_number'])->toBe('76')
            ->and($parsed['wagon_number'])->toBe('64209')
            ->and($parsed['wagon_type'])->toBe('HSM1');
    }
});

it('parses scale 17 shifted layout (UD1=operator, UD2=rake, UD3=wagon, UD4=type)', function (): void {
    $parsed = (new LoadriteUserDataParser)->parse(lrEvent([
        'Scale ID' => '17',
        'UserData1' => 'Harish',
        'UserData2' => '80',
        'UserData3' => '12377',
        'UserData4' => 'HL2D',
    ]));

    expect($parsed['rake_number'])->toBe('80')
        ->and($parsed['wagon_number'])->toBe('12377')
        ->and($parsed['wagon_type'])->toBe('HL2D')
        ->and($parsed['operator'])->toBe('Harish');
});

it('falls back to a heuristic for an unconfigured scale', function (): void {
    // Scale 99 not in config — heuristic: small int = rake, large int = wagon,
    // alphabetic token = type.
    $parsed = (new LoadriteUserDataParser)->parse(lrEvent([
        'Scale ID' => '99',
        'Operator' => 'Ramesh',
        'UserData1' => '80',
        'UserData2' => '53743',
        'UserData3' => 'HL',
    ]));

    expect($parsed['rake_number'])->toBe('80')
        ->and($parsed['wagon_number'])->toBe('53743')
        ->and($parsed['wagon_type'])->toBe('HL');
});

it('returns nulls when UserData is absent', function (): void {
    $parsed = (new LoadriteUserDataParser)->parse(lrEvent(['Scale ID' => '16']));

    expect($parsed['rake_number'])->toBeNull()
        ->and($parsed['wagon_number'])->toBeNull()
        ->and($parsed['wagon_type'])->toBeNull();
});
