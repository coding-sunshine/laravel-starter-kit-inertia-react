<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TransportWorkOrderRegistration;
use App\Support\TransportWorkOrderRegistrationSidingResolver;
use App\Support\TransportWorkOrderRegistrationSpreadsheetNormalizer;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

final readonly class ImportTransportWorkOrderRegistrationsFromExcelAction
{
    /**
     * @see database/excel/transpoter.xlsx column headings on row 1.
     *
     * @var array<string, array<int, string>>
     */
    private const array COLUMN_ALIASES = [
        'work_order_no_1' => ['work order no. 1'],
        'work_order_no_2' => ['work order no. 2'],
        'reference_no' => ['ref. no.', 'ref no', 'reference no', 'reference no.'],
        'work_order_date' => ['work order date'],
        'transporter_name' => ['transporter name'],
        'trade_name' => ['trade name'],
        'legal_name_of_business' => ['legal name of business'],
        'pan_card' => ['pan card'],
        'gst_no' => ['gst no.', 'gst no'],
        'status_raw' => ['status'],
        'email' => ['email address', 'email'],
        'vendor_code' => ['vendor code'],
        'mobile_1' => ['mob1', 'mob 1', 'mobile 1'],
        'mobile_2' => ['mob2', 'mob 2', 'mobile 2'],
        'address' => ['address'],
        'gramin_raw' => ['gramin / non-gramin', 'gramin / non gramin'],
    ];

    public function __construct(
        private TransportWorkOrderRegistrationSpreadsheetNormalizer $normalizer,
        private TransportWorkOrderRegistrationSidingResolver $sidingResolver,
    ) {}

    /**
     * Parse sheet into body rows + column indexes; mirrors live import prerequisites.
     *
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

        if (($indexes['work_order_no_2'] ?? null) === null) {
            throw new InvalidArgumentException('Spreadsheet header row must include column "Work Order No. 2".');
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
     * Preview import without writing: last spreadsheet row wins per {@see TransportWorkOrderRegistration::$work_order_no_2},
     * same as {@see handle()}. Whether each row would create or update is resolved against the database.
     *
     * @return array{
     *     stats: array{would_create: int, would_update: int, skipped: int},
     *     skipped: list<array{excel_row: int, reason: string}>,
     *     records: list<array{
     *         excel_row: int,
     *         work_order_no_2: string,
     *         outcome: 'create'|'update',
     *         attributes: array<string, mixed>
     *     }>
     * }
     */
    public function dryRun(string $absolutePath): array
    {
        $parsed = $this->parseSpreadsheetForImport($absolutePath);
        $bodyRows = $parsed['bodyRows'];
        $indexes = $parsed['indexes'];

        if ($bodyRows === [] || $indexes === []) {
            return [
                'stats' => ['would_create' => 0, 'would_update' => 0, 'skipped' => 0],
                'skipped' => [],
                'records' => [],
            ];
        }

        /** @var array<string, array{excel_row: int, attributes: array<string, mixed>}> $lastByWo2 */
        $lastByWo2 = [];
        $skipped = [];
        $skippedCount = 0;

        foreach ($bodyRows as $offset => $rowCells) {
            $excelRow = $offset + 2;
            $record = $this->buildAttributesFromRow($rowCells, $indexes);
            $wo2 = $record['work_order_no_2'];

            if ($wo2 === null || $wo2 === '') {
                $skippedCount++;
                $skipped[] = [
                    'excel_row' => $excelRow,
                    'reason' => 'Missing or empty Work Order No. 2',
                ];

                continue;
            }

            unset($record['work_order_no_2']);

            $lastByWo2[(string) $wo2] = [
                'excel_row' => $excelRow,
                'attributes' => array_merge(
                    ['work_order_no_2' => $wo2],
                    $record,
                ),
            ];
        }

        $records = [];
        $wouldCreate = 0;
        $wouldUpdate = 0;

        foreach ($lastByWo2 as $wo2Key => $item) {
            $exists = TransportWorkOrderRegistration::query()
                ->where('work_order_no_2', $wo2Key)
                ->exists();

            $outcome = $exists ? 'update' : 'create';
            if ($exists) {
                $wouldUpdate++;
            } else {
                $wouldCreate++;
            }

            $records[] = [
                'excel_row' => $item['excel_row'],
                'work_order_no_2' => $wo2Key,
                'outcome' => $outcome,
                'attributes' => $item['attributes'],
            ];
        }

        return [
            'stats' => [
                'would_create' => $wouldCreate,
                'would_update' => $wouldUpdate,
                'skipped' => $skippedCount,
            ],
            'skipped' => $skipped,
            'records' => $records,
        ];
    }

    /**
     * Import keyed uniquely by {@see TransportWorkOrderRegistration::$work_order_no_2}.
     * Later rows replace earlier ones when work order no. 2 repeats.
     *
     * @return array{created:int, updated:int, skipped:int}
     */
    public function handle(string $absolutePath): array
    {
        $parsed = $this->parseSpreadsheetForImport($absolutePath);
        $bodyRows = $parsed['bodyRows'];
        $indexes = $parsed['indexes'];

        if ($bodyRows === [] || $indexes === []) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        return DB::transaction(function () use ($bodyRows, $indexes): array {
            $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];

            foreach ($bodyRows as $rowCells) {
                $record = $this->buildAttributesFromRow($rowCells, $indexes);
                $wo2 = $record['work_order_no_2'];

                if ($wo2 === null || $wo2 === '') {
                    $stats['skipped']++;

                    continue;
                }

                unset($record['work_order_no_2']);

                $registration = TransportWorkOrderRegistration::query()->updateOrCreate(
                    ['work_order_no_2' => $wo2],
                    $record,
                );

                if ($registration->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }
            }

            return $stats;
        });
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
                $target = TransportWorkOrderRegistrationSpreadsheetNormalizer::normalizeHeaderLabel($alias);

                foreach ($headerCells as $colIndex => $headerCell) {
                    if (TransportWorkOrderRegistrationSpreadsheetNormalizer::normalizeHeaderLabel($headerCell) === $target) {
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

        $wo1 = $this->normalizer->nullableStringCell($get('work_order_no_1'));
        $wo2 = $this->normalizer->nullableStringCell($get('work_order_no_2'));

        $referenceNo = $this->normalizer->normalizeReferenceNo($get('reference_no'));
        $workOrderDate = $this->normalizer->normalizeWorkOrderDate($get('work_order_date'));

        /** @var array{is_active: bool, status: ?string} $statusParts */
        $statusParts = $this->normalizer->normalizeStatusColumns($this->stringFromCell($get('status_raw')));

        $sidingId = $this->sidingResolver->resolveSidingId($wo1, $wo2);

        return [
            'work_order_no_2' => $wo2,
            'siding_id' => $sidingId,
            'work_order_no_1' => $wo1,
            'reference_no' => $referenceNo,
            'work_order_date' => $workOrderDate,
            'transporter_name' => $this->normalizer->nullableStringCell($get('transporter_name')),
            'trade_name' => $this->normalizer->nullableStringCell($get('trade_name')),
            'legal_name_of_business' => $this->truncateNullableText($this->normalizer->nullableStringCell($get('legal_name_of_business'))),
            'pan_card' => $this->normalizer->nullableStringCell($get('pan_card')),
            'gst_no' => $this->normalizer->nullableStringCell($get('gst_no')),
            'status' => $statusParts['status'],
            'is_active' => $statusParts['is_active'],
            'email' => $this->normalizer->nullableStringCell($get('email')),
            'vendor_code' => $this->normalizer->nullableStringCell($get('vendor_code')),
            'mobile_1' => $this->normalizer->nullableStringCell($get('mobile_1')),
            'mobile_2' => $this->normalizer->nullableStringCell($get('mobile_2')),
            'address' => $this->truncateNullableText($this->normalizer->nullableStringCell($get('address'))),
            'gramin_or_non_gramin' => $this->normalizer->normalizeGraminOrNonGramin($get('gramin_raw')),
        ];
    }

    private function stringFromCell(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        return is_string($raw) ? $raw : (string) $raw;
    }

    private function truncateNullableText(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 65000);
    }
}
