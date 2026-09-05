<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VehicleWorkorder;
use App\Support\TransportWorkOrderRegistrationSidingResolver;
use App\Support\VehicleWorkorderSpreadsheetNormalizer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

final readonly class ImportVehicleWorkordersFromVehiclesSpreadsheetAction
{
    /**
     * @see database/excel/vehicles.xlsx headings on row 1.
     *
     * @var array<string, array<int, string>>
     */
    private const array COLUMN_ALIASES = [
        'vehicle_no' => ['regd. no', 'regd no'],
        'wo_no' => ['wo no'],
        'transport_name' => ['transport name'],
        'work_order_date' => ['work order date'],
        'issued_date' => ['issued date'],
        'represented_by' => ['represented by'],
        'place' => ['place'],
        'address' => ['address'],
        'tyres' => ['tyres'],
        'mobile_no_1' => ['mobile.no 1', 'mobile no 1'],
        'mobile_no_2' => ['mobile no 2'],
        'owner_type' => ['owner/not owner', 'owner / not owner'],
        'regd_date' => ['regd. date', 'regd date'],
        'permit_validity_date' => ['permit validity date'],
        'tax_validity_date' => ['tax validity date'],
        'fitness_validity_date' => ['fitness validity date'],
        'insurance_validity_date' => ['insurance validity date'],
        'maker_model' => ['maker / model', 'maker model'],
        'make' => ['make'],
        'rcd_pin_no' => ['rcd pin no'],
        'local_or_non_local' => ['local & non local', 'local and non local', 'local & non-local'],
        'recommended_by' => ['recommended by'],
        'remarks' => ['remarks'],
        'tare_weight' => ['tare weight'],
    ];

    public function __construct(
        private VehicleWorkorderSpreadsheetNormalizer $normalizer,
        private TransportWorkOrderRegistrationSidingResolver $sidingResolver,
    ) {}

    /**
     * @return array{
     *     bodyRows: list<array<mixed>>,
     *     indexes: array<string, int>
     * }
     *
     * @throws InvalidArgumentException
     */
    public function parseSpreadsheetForImport(string $absolutePath): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($absolutePath);

        /** @var list<list<mixed>> $sheetRows */
        $sheetRows = $spreadsheet->getActiveSheet()->toArray();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if ($sheetRows === []) {
            return ['bodyRows' => [], 'indexes' => []];
        }

        $headerCells = $sheetRows[0] ?? [];
        if (! is_array($headerCells)) {
            return ['bodyRows' => [], 'indexes' => []];
        }

        $indexes = $this->resolveColumnIndexes($headerCells);

        if (($indexes['vehicle_no'] ?? null) === null) {
            throw new InvalidArgumentException('Spreadsheet header row must include column "REGD. NO".');
        }

        $bodyRows = array_slice($sheetRows, 1);
        /** @var list<array<mixed>> $bodyRows */
        $bodyRows = array_values(array_map(
            fn (mixed $row): array => is_array($row) ? $row : [],
            $bodyRows,
        ));

        return ['bodyRows' => $bodyRows, 'indexes' => $indexes];
    }

    /**
     * @return array{
     *     stats: array{
     *         would_create: int,
     *         would_update: int,
     *         skipped: int,
     *         tare_weight_null_or_non_positive: int,
     *     },
     *     skipped: list<array{excel_row: int, reason: string}>,
     *     records: list<array{
     *         excel_row: int,
     *         vehicle_no: string,
     *         outcome: 'create'|'update',
     *         attributes: array<string, mixed>
     *     }>,
     *     tare_weight_issue_rows: list<array{
     *         vehicle_no: string,
     *         vehicle_workorder_id: int|null,
     *         outcome: 'create'|'update',
     *         tare_weight_xlsx: float|null,
     *         tare_weight_database: float|null,
     *     }>
     * }
     */
    public function dryRun(string $absolutePath): array
    {
        $parsed = $this->parseSpreadsheetForImport($absolutePath);

        if ($parsed['bodyRows'] === [] || $parsed['indexes'] === []) {
            return [
                'stats' => [
                    'would_create' => 0,
                    'would_update' => 0,
                    'skipped' => 0,
                    'tare_weight_null_or_non_positive' => 0,
                ],
                'skipped' => [],
                'records' => [],
                'tare_weight_issue_rows' => [],
            ];
        }

        $accumulated = $this->accumulateMergedFromBodyRows($parsed['bodyRows'], $parsed['indexes']);

        /** @var array<string, array{excel_row: int, attributes: array<string, mixed>}> $merged */
        $merged = $accumulated['merged'];

        $records = [];
        $wouldCreate = 0;
        $wouldUpdate = 0;
        $tareIssueRows = [];

        foreach ($merged as $item) {
            /** @var array<string, mixed> $attrs */
            $attrs = $item['attributes'];
            $vehicleNo = (string) $attrs['vehicle_no'];

            $existingRow = VehicleWorkorder::query()
                ->where('vehicle_no', $vehicleNo)
                ->first(['id', 'tare_weight']);

            $existingId = $existingRow !== null ? (int) $existingRow->id : null;
            $outcome = $existingId !== null ? 'update' : 'create';
            $databaseTare = $existingRow !== null
                ? $this->tareWeightNumericOrNull($existingRow->tare_weight)
                : null;

            $xlsxTare = $this->tareWeightNumericOrNull($attrs['tare_weight'] ?? null);

            if ($this->isSpreadsheetTareWeightNullOrNonPositive($attrs['tare_weight'] ?? null)) {
                $tareIssueRows[] = [
                    'vehicle_no' => $vehicleNo,
                    'vehicle_workorder_id' => $existingId,
                    'outcome' => $outcome,
                    'tare_weight_xlsx' => $xlsxTare,
                    'tare_weight_database' => $databaseTare,
                ];
            }

            if ($existingId !== null) {
                $wouldUpdate++;
            } else {
                $wouldCreate++;
            }

            $records[] = [
                'excel_row' => $item['excel_row'],
                'vehicle_no' => $vehicleNo,
                'outcome' => $outcome,
                'attributes' => $attrs,
            ];
        }

        return [
            'stats' => [
                'would_create' => $wouldCreate,
                'would_update' => $wouldUpdate,
                'skipped' => $accumulated['skipped_count'],
                'tare_weight_null_or_non_positive' => count($tareIssueRows),
            ],
            'skipped' => $accumulated['skipped'],
            'records' => $records,
            'tare_weight_issue_rows' => $tareIssueRows,
        ];
    }

    /**
     * First spreadsheet row wins per normalized {@see VehicleWorkorder::$vehicle_no}; later duplicates in the file are skipped.
     * Existing DB rows are updated with all mapped columns except {@see VehicleWorkorder::$tare_weight}; new rows receive tare from the sheet.
     *
     * @return array{
     *     created: int,
     *     updated: int,
     *     skipped: int,
     *     tare_weight_null_or_non_positive: int,
     *     tare_weight_issue_rows: list<array{
     *         vehicle_no: string,
     *         vehicle_workorder_id: int,
     *         outcome: 'create'|'update',
     *         tare_weight_xlsx: float|null,
     *         tare_weight_database: float|null,
     *     }>
     * }
     */
    public function handle(string $absolutePath): array
    {
        $parsed = $this->parseSpreadsheetForImport($absolutePath);

        if ($parsed['bodyRows'] === [] || $parsed['indexes'] === []) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'tare_weight_null_or_non_positive' => 0,
                'tare_weight_issue_rows' => [],
            ];
        }

        $accumulated = $this->accumulateMergedFromBodyRows($parsed['bodyRows'], $parsed['indexes']);

        return DB::transaction(function () use ($accumulated): array {
            /** @var array<string, array{excel_row: int, attributes: array<string, mixed>}> $merged */
            $merged = $accumulated['merged'];

            $stats = [
                'created' => 0,
                'updated' => 0,
                'skipped' => $accumulated['skipped_count'],
                'tare_weight_null_or_non_positive' => 0,
                'tare_weight_issue_rows' => [],
            ];

            foreach ($merged as $item) {
                /** @var array<string, mixed> $attrs */
                $attrs = $item['attributes'];
                $vehicleNo = (string) $attrs['vehicle_no'];
                $badTareSpreadsheet = $this->isSpreadsheetTareWeightNullOrNonPositive($attrs['tare_weight'] ?? null);
                $xlsxTare = $this->tareWeightNumericOrNull($attrs['tare_weight'] ?? null);

                $existing = VehicleWorkorder::query()
                    ->where('vehicle_no', $vehicleNo)
                    ->first();

                $databaseTareBefore = $existing !== null
                    ? $this->tareWeightNumericOrNull($existing->tare_weight)
                    : null;

                if ($existing !== null) {
                    unset($attrs['tare_weight']);
                    $existing->update($attrs);
                    $stats['updated']++;
                    $persistedId = (int) $existing->id;
                    $outcome = 'update';
                } else {
                    $created = VehicleWorkorder::query()->create($attrs);
                    $stats['created']++;
                    $persistedId = (int) $created->id;
                    $outcome = 'create';
                }

                if ($badTareSpreadsheet) {
                    $stats['tare_weight_null_or_non_positive']++;
                    $stats['tare_weight_issue_rows'][] = [
                        'vehicle_no' => $vehicleNo,
                        'vehicle_workorder_id' => $persistedId,
                        'outcome' => $outcome,
                        'tare_weight_xlsx' => $xlsxTare,
                        'tare_weight_database' => $databaseTareBefore,
                    ];
                }
            }

            return $stats;
        });
    }

    /**
     * @param  list<array<mixed>>  $bodyRows
     * @param  array<string, int>  $indexes
     * @return array{
     *     merged: array<string, array{excel_row: int, attributes: array<string, mixed>}>,
     *     skipped: list<array{excel_row: int, reason: string}>,
     *     skipped_count: int,
     * }
     */
    private function accumulateMergedFromBodyRows(array $bodyRows, array $indexes): array
    {
        $skippedRows = [];
        $skippedCount = 0;

        /** @var array<string, array{excel_row: int, attributes: array<string, mixed>}> $mergedByVehicleNo */
        $mergedByVehicleNo = [];

        foreach ($bodyRows as $offset => $rowCells) {
            $excelRow = $offset + 2;

            /** @var array<string, mixed> $attrs */
            $attrs = $this->buildAttributesFromRow($rowCells, $indexes);

            if ($attrs['vehicle_no'] === null || $attrs['vehicle_no'] === '') {
                $skippedCount++;
                $skippedRows[] = [
                    'excel_row' => $excelRow,
                    'reason' => 'Missing or invalid REGD. NO',
                ];

                continue;
            }

            if ($attrs['siding_id'] === null) {
                $skippedCount++;
                $skippedRows[] = [
                    'excel_row' => $excelRow,
                    'reason' => 'WO NO does not resolve to a siding (D/P/K)',
                ];

                continue;
            }

            $vehicleNoKey = (string) $attrs['vehicle_no'];

            if (isset($mergedByVehicleNo[$vehicleNoKey])) {
                $skippedCount++;
                $skippedRows[] = [
                    'excel_row' => $excelRow,
                    'reason' => 'Duplicate REGD. NO in spreadsheet (first row wins)',
                ];

                continue;
            }

            $mergedByVehicleNo[$vehicleNoKey] = [
                'excel_row' => $excelRow,
                'attributes' => $attrs,
            ];
        }

        return [
            'merged' => $mergedByVehicleNo,
            'skipped' => $skippedRows,
            'skipped_count' => $skippedCount,
        ];
    }

    /**
     * @param  array<mixed>  $headerCells
     * @return array<string, int>
     */
    private function resolveColumnIndexes(array $headerCells): array
    {
        $indexes = [];

        foreach (self::COLUMN_ALIASES as $key => $aliases) {
            foreach ($aliases as $alias) {
                $target = VehicleWorkorderSpreadsheetNormalizer::normalizeHeaderLabel($alias);

                foreach ($headerCells as $colIndex => $headerCell) {
                    if (VehicleWorkorderSpreadsheetNormalizer::normalizeHeaderLabel($headerCell) === $target) {
                        $indexes[$key] = is_int($colIndex) ? $colIndex : (int) $colIndex;

                        break 2;
                    }
                }
            }
        }

        return $indexes;
    }

    /**
     * @param  array<mixed>  $row
     * @param  array<string, int>  $indexes
     * @return array<string, mixed>
     */
    private function buildAttributesFromRow(array $row, array $indexes): array
    {
        $get = fn (string $logicalKey): mixed => isset($indexes[$logicalKey])
            ? ($row[$indexes[$logicalKey]] ?? null)
            : null;

        $vehicleNo = $this->normalizer->normalizeVehicleNo($get('vehicle_no'));
        $woNo = $this->normalizer->nullableStringCell($get('wo_no'));
        $sidingId = $this->sidingResolver->resolveSidingIdFromWoNo($woNo);

        return [
            'vehicle_no' => $vehicleNo,
            'siding_id' => $sidingId,
            'wo_no' => $woNo,
            'transport_name' => $this->normalizer->nullableStringCell($get('transport_name')),
            'work_order_date' => $this->normalizer->normalizeDate($get('work_order_date')),
            'issued_date' => $this->normalizer->normalizeDate($get('issued_date')),
            'represented_by' => $this->normalizer->nullableStringCell($get('represented_by')),
            'place' => $this->normalizer->nullableStringCell($get('place')),
            'address' => $this->truncateNullableText($this->normalizer->nullableStringCell($get('address'))),
            'tyres' => $this->normalizer->normalizeTyres($get('tyres')),
            'mobile_no_1' => $this->normalizer->nullableStringCell($get('mobile_no_1')),
            'mobile_no_2' => $this->normalizer->nullableStringCell($get('mobile_no_2')),
            'owner_type' => $this->normalizer->nullableStringCell($get('owner_type')),
            'regd_date' => $this->normalizer->normalizeDate($get('regd_date')),
            'permit_validity_date' => $this->normalizer->normalizeDate($get('permit_validity_date')),
            'tax_validity_date' => $this->normalizer->normalizeDate($get('tax_validity_date')),
            'fitness_validity_date' => $this->normalizer->normalizeDate($get('fitness_validity_date')),
            'insurance_validity_date' => $this->normalizer->normalizeDate($get('insurance_validity_date')),
            'maker_model' => $this->normalizer->nullableStringCell($get('maker_model')),
            'make' => $this->normalizer->nullableStringCell($get('make')),
            'rcd_pin_no' => $this->normalizer->nullableStringCell($get('rcd_pin_no')),
            'local_or_non_local' => $this->normalizer->nullableStringCell($get('local_or_non_local')),
            'recommended_by' => $this->normalizer->nullableStringCell($get('recommended_by')),
            'remarks' => $this->truncateNullableText($this->normalizer->nullableStringCell($get('remarks'))),
            'tare_weight' => $this->normalizer->parseTareWeight($get('tare_weight')),
        ];
    }

    private function truncateNullableText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 65000);
    }

    /**
     * Coerce model / cell tare to float for reporting, or null when unset or non-numeric.
     */
    private function tareWeightNumericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * True when the spreadsheet cell normalizes to null or a number ≤ 0 (includes zero and negatives).
     */
    private function isSpreadsheetTareWeightNullOrNonPositive(mixed $tareWeight): bool
    {
        if ($tareWeight === null) {
            return true;
        }

        if (! is_int($tareWeight) && ! is_float($tareWeight)) {
            return false;
        }

        return (float) $tareWeight <= 0.0;
    }
}
