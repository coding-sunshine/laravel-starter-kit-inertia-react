<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\RrWagonSnapshot;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class RakeWeighmentNetWeightValidator
{
    /**
     * @var array<string, string>
     */
    private const WAGON_FIELD_LABELS = [
        'cc_capacity_mt' => 'Carrying capacity (CC)',
        'actual_gross_mt' => 'Gross weight',
        'tare_weight_mt' => 'Tare weight',
        'actual_tare_mt' => 'Actual tare weight',
        'printed_tare_mt' => 'Printed tare weight',
        'net_weight_mt' => 'Net weight',
    ];

    /**
     * @var array<string, string>
     */
    private const TOTAL_FIELD_LABELS = [
        'total_cc_weight_mt' => 'Total carrying capacity (CC)',
        'total_gross_weight_mt' => 'Total gross weight',
        'total_tare_weight_mt' => 'Total tare weight',
        'total_net_weight_mt' => 'Total net weight',
    ];

    /**
     * @param  array<string, float|null>  $totals
     * @param  array<int, array<string, mixed>>  $wagonRows
     */
    public static function assertNonNegative(array $totals, array $wagonRows): void
    {
        foreach ($wagonRows as $row) {
            foreach (self::WAGON_FIELD_LABELS as $field => $label) {
                self::assertFieldNonNegative(
                    $row[$field] ?? null,
                    $label,
                    mb_trim((string) ($row['wagon_number'] ?? '')),
                );
            }
        }

        foreach (self::TOTAL_FIELD_LABELS as $field => $label) {
            self::assertFieldNonNegative(
                $totals[$field] ?? null,
                $label,
                null,
            );
        }
    }

    /**
     * @param  array<string, float|null>  $totals
     */
    public static function assertMinimumTotalNetWeight(array $totals, float $minimumMt = 1.0): void
    {
        $total = $totals['total_net_weight_mt'] ?? null;

        if ($total === null || ! is_numeric($total)) {
            throw new InvalidArgumentException(
                'Total net weight is required for railway receipt weighment.',
            );
        }

        if ((float) $total < $minimumMt) {
            throw new InvalidArgumentException(
                sprintf(
                    'Total net weight must be at least %.2f MT (got %s MT).',
                    $minimumMt,
                    $total,
                ),
            );
        }
    }

    /**
     * @param  Collection<int, RrWagonSnapshot>  $snapshots
     * @return array{totals: array<string, float|null>, wagon_rows: array<int, array<string, mixed>>}
     */
    public static function payloadFromRrSnapshots(Collection $snapshots, float $totalNetMt): array
    {
        $wagonRows = [];

        foreach ($snapshots as $snapshot) {
            $tareMt = self::numericOrNull($snapshot->tare_weight_mt);

            $wagonRows[] = [
                'wagon_number' => $snapshot->wagon_number,
                'net_weight_mt' => self::numericOrNull($snapshot->loaded_weight_mt),
                'cc_capacity_mt' => self::numericOrNull($snapshot->pcc_weight_mt),
                'actual_gross_mt' => self::numericOrNull($snapshot->gross_weight_mt),
                'tare_weight_mt' => $tareMt,
                'printed_tare_mt' => $tareMt,
            ];
        }

        return [
            'totals' => [
                'total_net_weight_mt' => $totalNetMt,
            ],
            'wagon_rows' => $wagonRows,
        ];
    }

    private static function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private static function assertFieldNonNegative(mixed $value, string $label, ?string $wagonNumber): void
    {
        if ($value === null || ! is_numeric($value)) {
            return;
        }

        $float = (float) $value;

        if ($float >= 0) {
            return;
        }

        $context = $wagonNumber !== null && $wagonNumber !== ''
            ? " for wagon {$wagonNumber}"
            : '';

        $hint = $label === 'Net weight' || $label === 'Total net weight'
            ? ' Check that Gross and Tare columns are not swapped.'
            : '';

        throw new InvalidArgumentException(
            sprintf(
                '%s cannot be negative%s (%s MT).%s',
                $label,
                $context,
                $value,
                $hint,
            ),
        );
    }
}
