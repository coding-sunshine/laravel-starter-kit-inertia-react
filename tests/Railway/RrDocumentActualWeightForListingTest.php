<?php

declare(strict_types=1);

use App\Models\RrDocument;
use App\Models\RrWagonSnapshot;

test('actualWeightMtForListing sums snapshot loaded weights when relation is populated', function (): void {
    $doc = new RrDocument([
        'rr_details' => ['actual_weight_mt' => 99.99],
    ]);
    $doc->setRelation(
        'wagonSnapshots',
        collect([
            new RrWagonSnapshot(['loaded_weight_mt' => 50.25]),
            new RrWagonSnapshot(['loaded_weight_mt' => 49.75]),
        ]),
    );

    expect($doc->actualWeightMtForListing())->toBe('100');
});

test('actualWeightMtForListing falls back to rr_details when no loaded weights', function (): void {
    $doc = new RrDocument([
        'rr_details' => ['actual_weight_mt' => 3522.5],
    ]);
    $doc->setRelation(
        'wagonSnapshots',
        collect([
            new RrWagonSnapshot(['loaded_weight_mt' => null]),
        ]),
    );

    expect($doc->actualWeightMtForListing())->toBe('3522.5');
});

test('actualWeightMtForListing falls back when wagon snapshots relation is empty', function (): void {
    $doc = new RrDocument([
        'rr_details' => ['actual_weight_mt' => 88.88],
    ]);
    $doc->setRelation('wagonSnapshots', collect());

    expect($doc->actualWeightMtForListing())->toBe('88.88');
});
