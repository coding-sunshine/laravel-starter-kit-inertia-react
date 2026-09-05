<?php

declare(strict_types=1);

use App\Support\RakeWeighmentNetWeightValidator;

test('rejects negative net weight on a wagon row', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        ['total_net_weight_mt' => 100.0],
        [
            ['wagon_number' => '12345678', 'net_weight_mt' => -1.0],
        ],
    );
})->throws(InvalidArgumentException::class, 'Net weight cannot be negative for wagon 12345678');

test('rejects negative total net weight', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        ['total_net_weight_mt' => -100.0],
        [
            ['wagon_number' => '12345678', 'net_weight_mt' => 60.0],
        ],
    );
})->throws(InvalidArgumentException::class, 'Total net weight cannot be negative');

test('allows zero net weight on wagon and total', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        ['total_net_weight_mt' => 0.0],
        [
            ['wagon_number' => '12345678', 'net_weight_mt' => 0.0],
        ],
    );

    expect(true)->toBeTrue();
});

test('allows null net weights', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        ['total_net_weight_mt' => null],
        [
            ['wagon_number' => '12345678', 'net_weight_mt' => null],
        ],
    );

    expect(true)->toBeTrue();
});

test('rejects negative gross weight on a wagon row', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        ['total_net_weight_mt' => 100.0],
        [
            [
                'wagon_number' => '12345678',
                'actual_gross_mt' => -50.0,
                'tare_weight_mt' => 20.0,
                'cc_capacity_mt' => 70.0,
                'net_weight_mt' => 30.0,
            ],
        ],
    );
})->throws(InvalidArgumentException::class, 'Gross weight cannot be negative for wagon 12345678');

test('rejects negative tare weight on a wagon row', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        [],
        [
            ['wagon_number' => 'WRGN001', 'tare_weight_mt' => -1.5],
        ],
    );
})->throws(InvalidArgumentException::class, 'Tare weight cannot be negative for wagon WRGN001');

test('rejects negative carrying capacity on a wagon row', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        [],
        [
            ['wagon_number' => 'WRGN002', 'cc_capacity_mt' => -0.01],
        ],
    );
})->throws(InvalidArgumentException::class, 'Carrying capacity (CC) cannot be negative for wagon WRGN002');

test('rejects negative total gross weight', function (): void {
    RakeWeighmentNetWeightValidator::assertNonNegative(
        ['total_gross_weight_mt' => -10.0],
        [
            ['wagon_number' => '12345678', 'net_weight_mt' => 60.0],
        ],
    );
})->throws(InvalidArgumentException::class, 'Total gross weight cannot be negative');

test('rejects total net weight below one metric tonne', function (): void {
    RakeWeighmentNetWeightValidator::assertMinimumTotalNetWeight(
        ['total_net_weight_mt' => 0.99],
    );
})->throws(InvalidArgumentException::class, 'Total net weight must be at least 1.00 MT');

test('rejects zero total net weight for fetch rr', function (): void {
    RakeWeighmentNetWeightValidator::assertMinimumTotalNetWeight(
        ['total_net_weight_mt' => 0.0],
    );
})->throws(InvalidArgumentException::class, 'Total net weight must be at least 1.00 MT');

test('rejects negative total net weight for fetch rr', function (): void {
    RakeWeighmentNetWeightValidator::assertMinimumTotalNetWeight(
        ['total_net_weight_mt' => -10.0],
    );
})->throws(InvalidArgumentException::class, 'Total net weight must be at least 1.00 MT');

test('allows total net weight of one metric tonne', function (): void {
    RakeWeighmentNetWeightValidator::assertMinimumTotalNetWeight(
        ['total_net_weight_mt' => 1.0],
    );

    expect(true)->toBeTrue();
});

test('rejects negative loaded weight on rr snapshot payload', function (): void {
    $snapshots = collect([
        (object) [
            'wagon_number' => '12345678',
            'loaded_weight_mt' => -5.0,
            'permissible_weight_mt' => 70.0,
            'gross_weight_mt' => null,
            'tare_weight_mt' => null,
        ],
    ]);

    $payload = RakeWeighmentNetWeightValidator::payloadFromRrSnapshots($snapshots, 100.0);

    RakeWeighmentNetWeightValidator::assertNonNegative($payload['totals'], $payload['wagon_rows']);
})->throws(InvalidArgumentException::class, 'Net weight cannot be negative for wagon 12345678');

test('includes wagon number in error message when provided', function (): void {
    try {
        RakeWeighmentNetWeightValidator::assertNonNegative(
            ['total_net_weight_mt' => 100.0],
            [
                ['wagon_number' => 'WRGN001', 'net_weight_mt' => -5.5],
            ],
        );

        expect(false)->toBeTrue('Expected InvalidArgumentException was not thrown');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('wagon WRGN001')
            ->and($e->getMessage())->toContain('Gross and Tare');
    }
});
