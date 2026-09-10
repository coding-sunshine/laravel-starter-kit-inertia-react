<?php

declare(strict_types=1);

use App\Support\Loadrite\TimestampSanity;
use Carbon\CarbonImmutable;

// ---------------------------------------------------------------------------
// isReasonablePast
// ---------------------------------------------------------------------------

it('accepts past timestamps', function (): void {
    $past = CarbonImmutable::now()->subHour();

    expect(TimestampSanity::isReasonablePast($past))->toBeTrue();
});

it('rejects future timestamps', function (): void {
    $future = CarbonImmutable::now()->addHour();

    expect(TimestampSanity::isReasonablePast($future))->toBeFalse();
});

it('rejects null for isReasonablePast', function (): void {
    expect(TimestampSanity::isReasonablePast(null))->toBeFalse();
});

// ---------------------------------------------------------------------------
// isReasonableTurnaround
// ---------------------------------------------------------------------------

it('accepts in-order timestamps within window', function (): void {
    $start = CarbonImmutable::now()->subHours(6);
    $end = CarbonImmutable::now()->subHour();

    expect(TimestampSanity::isReasonableTurnaround($start, $end))->toBeTrue();
});

it('rejects end before start', function (): void {
    $start = CarbonImmutable::now()->subHour();
    $end = CarbonImmutable::now()->subHours(2);

    expect(TimestampSanity::isReasonableTurnaround($start, $end))->toBeFalse();
});

it('rejects delta above max days', function (): void {
    // 16 days apart > 14-day MAX_REASONABLE_TURNAROUND_DAYS
    $start = CarbonImmutable::now()->subDays(17);
    $end = CarbonImmutable::now()->subDay();

    expect(TimestampSanity::isReasonableTurnaround($start, $end))->toBeFalse();
});

it('accepts custom max-days override', function (): void {
    // 19 days apart — fails the default 14-day limit but passes a 30-day limit.
    $start = CarbonImmutable::now()->subDays(20);
    $end = CarbonImmutable::now()->subDay();

    expect(TimestampSanity::isReasonableTurnaround($start, $end, 30))->toBeTrue()
        ->and(TimestampSanity::isReasonableTurnaround($start, $end, 14))->toBeFalse();
});

it('rejects null start for isReasonableTurnaround', function (): void {
    $end = CarbonImmutable::now()->subHour();

    expect(TimestampSanity::isReasonableTurnaround(null, $end))->toBeFalse();
});

it('rejects null end for isReasonableTurnaround', function (): void {
    $start = CarbonImmutable::now()->subHours(2);

    expect(TimestampSanity::isReasonableTurnaround($start, null))->toBeFalse();
});

it('rejects future start for isReasonableTurnaround', function (): void {
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);

    expect(TimestampSanity::isReasonableTurnaround($start, $end))->toBeFalse();
});
