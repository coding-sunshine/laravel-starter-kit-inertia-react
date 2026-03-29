<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVehicleDispatchRequest;
use App\Models\DispatchReport;
use App\Models\Siding;
use App\Models\VehicleDispatch;
use DateTimeImmutable;
use DOMDocument;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

final class VehicleDispatchController extends Controller
{
    /**
     * Fixed coal-mine → siding distances (km). Used when importing DPR/dispatch paste data
     * where the distance column reflects mine-to-siding haul, not power-plant-to-siding.
     *
     * @var list<array{km: float, siding_code: string}>
     */
    private const COAL_MINE_TO_SIDING_KM = [
        ['km' => 71.0, 'siding_code' => 'DUMK'],
        ['km' => 55.0, 'siding_code' => 'PKUR'],
        ['km' => 73.0, 'siding_code' => 'KURWA'],
    ];

    public function index(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();

        // Default to current date when no date filters are provided
        if (! $request->date && ! $request->date_from && ! $request->date_to) {
            return redirect()->route('vehicle-dispatch.index', array_merge(
                ['date' => now()->format('Y-m-d')],
                $request->only(['permit_no', 'truck_regd_no', 'tab'])
            ));
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $query = VehicleDispatch::with(['siding', 'creator'])
            ->whereIn('siding_id', $sidingIds)
            ->when($request->date_from, fn ($q) => $q->whereDate('issued_on', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('issued_on', '<=', $request->date_to))
            ->when($request->date && ! $request->date_from && ! $request->date_to, fn ($q) => $q->forDate($request->get('date')))
            ->when($request->permit_no, fn ($q) => $q->byPermitNo($request->permit_no))
            ->when($request->truck_regd_no, fn ($q) => $q->byTruckRegdNo($request->truck_regd_no))
            ->orderBy('issued_on', 'desc')
            ->orderBy('created_at', 'desc');

        $vehicleDispatches = $query->paginate(25);
        // dd($vehicleDispatches);
        $availableDates = VehicleDispatch::selectRaw('DATE(issued_on) as date')
            ->whereNotNull('issued_on')
            ->whereIn('siding_id', $sidingIds)
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date');

        // Get available sidings for import dropdown
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $dispatchReportsQuery = DispatchReport::with('siding')
            ->whereIn('siding_id', $sidingIds)
            ->when($request->date_from, fn ($q) => $q->whereDate('issued_on', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('issued_on', '<=', $request->date_to))
            ->when($request->get('date') && ! $request->date_from && ! $request->date_to, fn ($q) => $q->whereDate('issued_on', $request->get('date')))
            ->orderBy('issued_on', 'desc')
            ->orderBy('id', 'asc');

        $dispatchReports = $dispatchReportsQuery->get();

        // dd($dispatchReports);
        return Inertia::render('VehicleDispatch/Index', [
            'vehicleDispatches' => $vehicleDispatches,
            'dispatchReports' => $dispatchReports,
            'filters' => $request->only(['date_from', 'date_to', 'date', 'permit_no', 'truck_regd_no']),
            'tab' => $request->get('tab', 'main-data'),
            'availableDates' => $availableDates,
            'sidings' => $sidings,
            'preview_data' => $request->session()->get('preview_data', []),
            'import_target_date' => $request->session()->get('import_target_date'),
            'flash' => [
                'success' => $request->session()->get('success'),
                'import_errors' => $request->session()->get('import_errors'),
            ],
        ]);
    }

    public function update(UpdateVehicleDispatchRequest $request, VehicleDispatch $vehicleDispatch): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['issued_on'])) {
            try {
                $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['issued_on'])
                    ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $data['issued_on'])
                    ?: DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $data['issued_on'])
                    ?: new DateTimeImmutable($data['issued_on']);
                if ($dt) {
                    $data['shift'] = VehicleDispatch::shiftFromIssuedOn($dt);
                }
            } catch (Throwable) {
                // Leave shift unchanged if date parse fails
            }
        }
        $vehicleDispatch->update($data);

        $filters = $request->input('_filters', []);
        $query = is_array($filters) ? array_filter($filters) : [];

        return redirect()
            ->route('vehicle-dispatch.index', $query)
            ->with('success', 'Vehicle dispatch record updated successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|string',
            'target_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $request->input('data');
        $targetDate = $request->input('target_date', now()->format('Y-m-d'));

        // Parse Excel-style paste data
        $rows = $this->parsePasteData($data);

        if (empty($rows)) {
            throw ValidationException::withMessages(['data' => 'No valid data found in the paste content.']);
        }

        // Parse and validate all rows without saving to database
        $parsedRows = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $parsedData = $this->parseVehicleDispatchRow($row);

                // Check for unique Pass No during import preview
                if (! empty($parsedData['pass_no'])) {
                    $existingPass = VehicleDispatch::where('pass_no', $parsedData['pass_no'])->first();
                    if ($existingPass) {
                        // Stop immediately and throw error for duplicate Pass No
                        throw new InvalidArgumentException("Pass No '{$parsedData['pass_no']}' already exists. Import stopped.");
                    }
                }

                $parsedRows[] = $parsedData;
            } catch (Exception $e) {
                // Stop processing on first error
                $errors[] = 'Row '.($index + 2).': '.$e->getMessage();
                break; // Exit the loop immediately
            }
        }

        if (! empty($errors)) {
            return redirect()
                ->route('vehicle-dispatch.index', array_merge(['date' => $targetDate], $request->only(['permit_no', 'truck_regd_no'])))
                ->with('import_errors', $errors)
                ->with('import_target_date', $targetDate);
        }

        // Redirect back to index with preview data in session (keeps URL as /vehicle-dispatch)
        return redirect()
            ->route('vehicle-dispatch.index', array_merge(['date' => $targetDate], $request->only(['permit_no', 'truck_regd_no'])))
            ->with('preview_data', $parsedRows)
            ->with('import_target_date', $targetDate);
    }

    public function saveImport(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'target_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $request->input('data');
        $targetDate = $request->input('target_date', now()->format('Y-m-d'));

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($data, $targetDate, &$imported, &$errors) {
            foreach ($data as $index => $row) {
                try {
                    // Remove siding object data before saving (only keep siding_id)
                    if (isset($row['siding'])) {
                        unset($row['siding']);
                    }

                    // Validate required fields before saving
                    $this->validateVehicleDispatchData($row);

                    // Apply target date to each record if no issued_on is present
                    if (empty($row['issued_on'])) {
                        $row['issued_on'] = $targetDate.' '.now()->format('H:i:s');
                    }

                    VehicleDispatch::create($row);
                    $imported++;
                } catch (Exception $e) {
                    $errors[] = 'Row '.($index + 1).': '.$e->getMessage();
                }
            }
        });

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'save_errors' => $errors,
            ]);
        }

        return redirect()->route('vehicle-dispatch.index', ['date' => $targetDate])
            ->with('success', "Successfully imported {$imported} vehicle dispatch records.");
    }

    private function getVehicleDispatches($request)
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();
        $query = VehicleDispatch::query()
            ->with(['siding', 'creator'])
            ->whereIn('siding_id', $sidingIds)
            ->when($request->date, fn ($q) => $q->whereDate('issued_on', $request->date))
            ->when($request->permit_no, fn ($q) => $q->byPermitNo($request->permit_no))
            ->when($request->truck_regd_no, fn ($q) => $q->byTruckRegdNo($request->truck_regd_no))
            ->orderBy('issued_on', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate(25);
    }

    private function getAvailableSidings($request)
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        return Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function validateVehicleDispatchData(array $row): void
    {
        // Validate required fields
        if (empty($row['permit_no'])) {
            throw new InvalidArgumentException('Permit No is required');
        }

        if (empty($row['pass_no'])) {
            throw new InvalidArgumentException('Pass No is required');
        }

        if (empty($row['truck_regd_no'])) {
            throw new InvalidArgumentException('Truck Regd No is required');
        }

        if (empty($row['mineral'])) {
            throw new InvalidArgumentException('Mineral is required');
        }

        if ($row['mineral_weight'] === null || $row['mineral_weight'] === '') {
            throw new InvalidArgumentException('Mineral Weight is required and must be a valid number');
        }

        if (! is_numeric($row['mineral_weight']) || (float) $row['mineral_weight'] < 0) {
            throw new InvalidArgumentException('Mineral Weight must be a valid positive number');
        }

        // Validate that siding_id is present (should be determined from distance)
        if (empty($row['siding_id'])) {
            throw new InvalidArgumentException('Siding could not be determined from distance. Please check the distance value.');
        }
    }

    /**
     * Resolve siding from coal-mine-to-siding distance using fixed km → siding code mapping.
     */
    private function findSidingByDistance(?float $distance): ?int
    {
        if ($distance === null || $distance <= 0) {
            return null;
        }

        foreach (self::COAL_MINE_TO_SIDING_KM as $row) {
            if (abs($row['km'] - $distance) < 0.01) {
                return $this->sidingIdForDispatchCode($row['siding_code']);
            }
        }

        $bestCode = null;
        $bestDiff = null;
        foreach (self::COAL_MINE_TO_SIDING_KM as $row) {
            $diff = abs($row['km'] - $distance);
            if ($diff <= 1.0 && ($bestDiff === null || $diff < $bestDiff)) {
                $bestDiff = $diff;
                $bestCode = $row['siding_code'];
            }
        }

        return $bestCode !== null ? $this->sidingIdForDispatchCode($bestCode) : null;
    }

    private function sidingIdForDispatchCode(string $sidingCode): ?int
    {
        return Siding::query()->where('code', $sidingCode)->value('id');
    }

    /**
     * Get siding information for preview display
     */
    private function getSidingInfo(?int $sidingId): ?array
    {
        if (! $sidingId) {
            return null;
        }

        $siding = Siding::find($sidingId);

        return $siding ? [
            'id' => $siding->id,
            'name' => $siding->name,
            'code' => $siding->code,
        ] : null;
    }

    /**
     * Extract table rows from HTML table markup (e.g. when pasting from web pages).
     * Returns array of row arrays (each row = array of cell values), or empty if not HTML table.
     */
    private function parseHtmlTableToRows(string $data): array
    {
        $data = mb_trim($data);
        if ($data === '' || (mb_strpos($data, '<table') === false && mb_strpos($data, '<tr') === false && mb_strpos($data, '<td') === false)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $wrapped = mb_convert_encoding('<html><body>'.$data.'</body></html>', 'HTML-ENTITIES', 'UTF-8');
        if (! @$dom->loadHTML($wrapped)) {
            libxml_clear_errors();

            return [];
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $rows = [];
        if (! $body) {
            libxml_clear_errors();

            return [];
        }

        foreach ($body->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                $cells[] = mb_trim($td->textContent ?? '');
            }
            foreach ($tr->getElementsByTagName('th') as $th) {
                $cells[] = mb_trim($th->textContent ?? '');
            }
            if (count($cells) >= 4) {
                $rows[] = $cells;
            }
        }
        libxml_clear_errors();

        return $rows;
    }

    private function parsePasteData(string $data): array
    {
        // If pasted content looks like HTML table, extract rows from it first
        $htmlRows = $this->parseHtmlTableToRows($data);
        if (! empty($htmlRows)) {
            $filtered = [];
            $headerSkipped = false;
            foreach ($htmlRows as $columns) {
                if (! $headerSkipped && $this->isHeaderRow($columns)) {
                    $headerSkipped = true;

                    continue;
                }
                if (count($columns) >= 4) {
                    $filtered[] = $columns;
                }
            }

            return $filtered;
        }

        // Decode HTML entities (e.g. &nbsp; when copying from web tables)
        $data = html_entity_decode($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip any remaining HTML tags (handles partial HTML paste)
        $data = strip_tags($data);
        // Normalize line endings
        $data = str_replace(["\r\n", "\r"], "\n", mb_trim($data));
        $lines = explode("\n", $data);
        $rows = [];
        $headerSkipped = false;

        foreach ($lines as $lineIndex => $line) {
            $line = mb_trim($line);
            if (empty($line)) {
                continue;
            }

            // Try different splitting methods for Excel data
            $columns = null;

            // Method 1: Split by tabs
            if (mb_strpos($line, "\t") !== false) {
                $columns = explode("\t", $line);
            }
            // Method 2: Split by pipe character |
            elseif (mb_strpos($line, '|') !== false) {
                $columns = explode('|', $line);
            }
            // Method 3: Split by multiple spaces
            elseif (preg_match('/\s{2,}/', $line)) {
                $columns = preg_split('/\s{2,}/', $line);
            }
            // Method 4: Split by comma (CSV-style paste; str_getcsv handles quoted strings)
            elseif (mb_strpos($line, ',') !== false) {
                $columns = str_getcsv($line, ',');
            }
            // Method 5: Single spaces as fallback
            else {
                $columns = str_getcsv($line, ' ');
            }

            // Trim columns but preserve positions - do NOT remove empty cells (would shift indices)
            if ($columns) {
                $columns = array_map(fn ($col) => mb_trim((string) $col), $columns);
            }

            // Skip if we don't have enough columns (at least 4 for basic data)
            if (! $columns || count($columns) < 4) {
                continue;
            }

            // Skip header rows - detect common header patterns
            if (! $headerSkipped && $this->isHeaderRow($columns)) {
                $headerSkipped = true;

                continue;
            }

            $rows[] = $columns;
        }

        return $rows;
    }

    private function isHeaderRow(array $columns): bool
    {
        // Check if first row contains common header keywords
        $headerKeywords = [
            'serial', 'sl.', 'permit', 'pass', 'truck', 'mineral', 'weight', 'source',
            'destination', 'consignee', 'gate', 'distance', 'shift', 'issued', 'stack',
            'ref', 'do', 'regd', 'type', 'check', 'km',
        ];

        $firstColumn = mb_strtolower(mb_trim($columns[0] ?? ''));
        $lastColumn = mb_strtolower(mb_trim($columns[count($columns) - 1] ?? ''));

        // Check if any column contains header keywords
        foreach ($columns as $column) {
            $columnLower = mb_strtolower(mb_trim($column));
            foreach ($headerKeywords as $keyword) {
                if (mb_strpos($columnLower, $keyword) !== false) {
                    return true;
                }
            }
        }

        // Also check if first column looks like a header (not a number)
        if (! is_numeric($firstColumn) && mb_strlen($firstColumn) > 2) {
            return true;
        }

        return false;
    }

    private function parseVehicleDispatchRow(array $row): array
    {
        $data = null;

        // Format D: 14 cols, no Ref - Sl.No|Permit|Pass|StackDO|IssuedOn|Truck|Mineral|MinType|Weight|Source|Dest|Consignee|CheckGate|Distance
        if (count($row) >= 14) {
            $formatD = $this->mapFormatD($row);
            if ($this->hasValidRequiredFields($formatD)) {

                $data = $formatD;
            }
        }

        if (! $data) {
            $formatA = $this->mapFormatA($row);
            if ($this->hasValidRequiredFields($formatA)) {
                $data = $formatA;
            }
        }

        if (! $data) {
            $formatB = $this->mapFormatB($row);
            if ($this->hasValidRequiredFields($formatB)) {
                $data = $formatB;
            }
        }

        if (! $data) {
            $formatC = $this->mapFormatC($row);
            if ($this->hasValidRequiredFields($formatC)) {
                $data = $formatC;
            }
        }

        if (! $data) {
            $data = $this->mapWithWeightScan($row);
        }

        return $this->applyShiftFromIssuedOn($data);
    }

    private function applyShiftFromIssuedOn(array $data): array
    {
        $issuedOn = $data['issued_on'] ?? null;
        if ($issuedOn) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $issuedOn);
            if ($dt) {
                $data['shift'] = VehicleDispatch::shiftFromIssuedOn($dt);
            }
        }

        return $data;
    }

    private function hasValidRequiredFields(array $data): bool
    {
        return ! empty($data['permit_no'])
            && ! empty($data['pass_no'])
            && ! empty($data['truck_regd_no'])
            && ! empty($data['mineral'])
            && $data['mineral_weight'] !== null
            && is_numeric($data['mineral_weight'])
            && (float) $data['mineral_weight'] > 0;
    }

    /** Format A: 6=Truck, 7=Mineral, 8=MinType, 9=MinWeight */
    private function mapFormatA(array $row): array
    {
        $distance = $this->parseNullableInt($row[14] ?? null);
        $sidingId = $this->findSidingByDistance($distance);
        $sidingInfo = $this->getSidingInfo($sidingId);

        return [
            'siding_id' => $sidingId,
            'siding' => $sidingInfo,
            'serial_no' => $this->parseNullableInt($row[0] ?? null),
            'ref_no' => $this->parseNullableInt($row[1] ?? null),
            'permit_no' => $this->parseNullableString($row[2] ?? null),
            'pass_no' => $this->parseNullableString($row[3] ?? null),
            'stack_do_no' => $this->parseNullableString($row[4] ?? null),
            'issued_on' => $this->parseTimestamp($row[5] ?? null),
            'truck_regd_no' => $this->parseNullableString($row[6] ?? null),
            'mineral' => $this->parseNullableString($row[7] ?? null),
            'mineral_type' => $this->parseNullableString($row[8] ?? null),
            'mineral_weight' => $this->parseFlexibleDecimal($row[9] ?? null),
            'source' => $this->parseNullableString($row[10] ?? null),
            'destination' => $this->parseNullableString($row[11] ?? null),
            'consignee' => $this->parseNullableString($row[12] ?? null),
            'check_gate' => $this->parseNullableString($row[13] ?? null),
            'distance_km' => $distance,
            'shift' => $this->parseNullableString($row[15] ?? null),
            'created_by' => auth()->id(),
        ];
    }

    /** Format B: 6=Mineral, 7=MinType, 8=MinWeight, 15=Truck */
    private function mapFormatB(array $row): array
    {
        $distance = $this->parseNullableInt($row[13] ?? null);
        $sidingId = $this->findSidingByDistance($distance);
        $sidingInfo = $this->getSidingInfo($sidingId);

        return [
            'siding_id' => $sidingId,
            'siding' => $sidingInfo,
            'serial_no' => $this->parseNullableInt($row[0] ?? null),
            'ref_no' => $this->parseNullableInt($row[1] ?? null),
            'permit_no' => $this->parseNullableString($row[2] ?? null),
            'pass_no' => $this->parseNullableString($row[3] ?? null),
            'stack_do_no' => $this->parseNullableString($row[4] ?? null),
            'issued_on' => $this->parseTimestamp($row[5] ?? null),
            'mineral' => $this->parseNullableString($row[6] ?? null),
            'mineral_type' => $this->parseNullableString($row[7] ?? null),
            'mineral_weight' => $this->parseFlexibleDecimal($row[8] ?? null),
            'source' => $this->parseNullableString($row[9] ?? null),
            'destination' => $this->parseNullableString($row[10] ?? null),
            'consignee' => $this->parseNullableString($row[11] ?? null),
            'check_gate' => $this->parseNullableString($row[12] ?? null),
            'distance_km' => $distance,
            'shift' => $this->parseNullableString($row[14] ?? null),
            'truck_regd_no' => $this->parseNullableString($row[15] ?? null),
            'created_by' => auth()->id(),
        ];
    }

    /** Format C: 9 columns - 6=Truck, 7=Mineral, 8=MinWeight (no mineral type) */
    private function mapFormatC(array $row): array
    {
        $sidingId = $this->findSidingByDistance(null); // No distance in this format
        $sidingInfo = $this->getSidingInfo($sidingId);

        return [
            'siding_id' => $sidingId,
            'siding' => $sidingInfo,
            'serial_no' => $this->parseNullableInt($row[0] ?? null),
            'ref_no' => $this->parseNullableInt($row[1] ?? null),
            'permit_no' => $this->parseNullableString($row[2] ?? null),
            'pass_no' => $this->parseNullableString($row[3] ?? null),
            'stack_do_no' => $this->parseNullableString($row[4] ?? null),
            'issued_on' => $this->parseTimestamp($row[5] ?? null),
            'truck_regd_no' => $this->parseNullableString($row[6] ?? null),
            'mineral' => $this->parseNullableString($row[7] ?? null),
            'mineral_type' => null,
            'mineral_weight' => $this->parseFlexibleDecimal($row[8] ?? null),
            'source' => $this->parseNullableString($row[9] ?? null),
            'destination' => $this->parseNullableString($row[10] ?? null),
            'consignee' => null,
            'check_gate' => null,
            'distance_km' => null,
            'shift' => null,
            'created_by' => auth()->id(),
        ];
    }

    /** Format D: 14 columns, no Ref - Sl.No|Permit|Pass|StackDO|IssuedOn|Truck|Mineral|MinType|Weight|Source|Dest|Consignee|CheckGate|Distance */
    private function mapFormatD(array $row): array
    {
        $distance = $this->parseNullableInt($row[13] ?? null);
        $sidingId = $this->findSidingByDistance($distance);
        $sidingInfo = $this->getSidingInfo($sidingId);

        return [
            'siding_id' => $sidingId,
            'siding' => $sidingInfo,
            'serial_no' => $this->parseNullableInt($row[0] ?? null),
            'ref_no' => $this->parseNullableInt($row[0] ?? null),
            'permit_no' => $this->parseNullableString($row[1] ?? null),
            'pass_no' => $this->parseNullableString($row[2] ?? null),
            'stack_do_no' => $this->parseNullableString($row[3] ?? null),
            'issued_on' => $this->parseTimestamp($row[4] ?? null),
            'truck_regd_no' => $this->parseNullableString($row[5] ?? null),
            'mineral' => $this->parseNullableString($row[6] ?? null),
            'mineral_type' => $this->parseNullableString($row[7] ?? null),
            'mineral_weight' => $this->parseFlexibleDecimal($row[8] ?? null),
            'source' => $this->parseNullableString($row[9] ?? null),
            'destination' => $this->parseNullableString($row[10] ?? null),
            'consignee' => $this->parseNullableString($row[11] ?? null),
            'check_gate' => $this->parseNullableString($row[12] ?? null),
            'distance_km' => $distance,
            'shift' => null,
            'created_by' => auth()->id(),
        ];
    }

    /** Fallback: scan cols 6-11 for first valid weight, infer other fields */
    private function mapWithWeightScan(array $row): array
    {
        $weightCol = null;
        for ($i = 6; $i <= 11; $i++) {
            $w = $this->parseFlexibleDecimal($row[$i] ?? null);
            if ($w !== null && $w > 0 && $w < 10000) {
                $weightCol = $i;
                break;
            }
        }

        // If we found weight at col 8 with 14 columns → Format D
        if ($weightCol === 8 && count($row) >= 14) {
            return $this->mapFormatD($row);
        }
        if ($weightCol === 8) {
            return count($row) <= 9
                ? $this->mapFormatC($row)
                : $this->mapFormatB($row);
        }
        if ($weightCol === 9) {
            return $this->mapFormatA($row);
        }
        if ($weightCol === 7) {
            // Mineral at 6, Weight at 7 (6 used for both truck and mineral when only 8 cols)
            $sidingId = $this->findSidingByDistance(null); // No distance in this format
            $sidingInfo = $this->getSidingInfo($sidingId);

            return [
                'siding_id' => $sidingId,
                'siding' => $sidingInfo,
                'serial_no' => $this->parseNullableInt($row[0] ?? null),
                'ref_no' => $this->parseNullableInt($row[1] ?? null),
                'permit_no' => $this->parseNullableString($row[2] ?? null),
                'pass_no' => $this->parseNullableString($row[3] ?? null),
                'stack_do_no' => $this->parseNullableString($row[4] ?? null),
                'issued_on' => $this->parseTimestamp($row[5] ?? null),
                'truck_regd_no' => $this->parseNullableString($row[6] ?? null),
                'mineral' => $this->parseNullableString($row[6] ?? null),
                'mineral_type' => null,
                'mineral_weight' => $this->parseFlexibleDecimal($row[7] ?? null),
                'source' => $this->parseNullableString($row[8] ?? null),
                'destination' => $this->parseNullableString($row[9] ?? null),
                'consignee' => null,
                'check_gate' => null,
                'distance_km' => null,
                'shift' => null,
                'created_by' => auth()->id(),
            ];
        }

        // Last resort: Format A
        return $this->mapFormatA($row);
    }

    private function parseNullableInt($value): ?int
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function parseRequiredString($value, string $fieldName): string
    {
        if ($value === null || $value === '' || $value === '-') {
            throw new InvalidArgumentException("{$fieldName} is required");
        }

        return mb_trim((string) $value);
    }

    private function parseNullableString($value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        return mb_trim((string) $value);
    }

    private function parseTimestamp($value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        // Clean the value first
        $cleanValue = mb_trim($value);

        // Try to parse various date formats (02-Mar-2026 23:59 = 11:59 PM = 3rd shift)
        // Note: H:i A misparses "23:59 PM" as 11:59 AM; use H:i * to treat 23:59 as 24h and absorb trailing AM/PM
        $formats = [
            'd-M-Y h:i A',  // 02-Mar-2026 11:59 PM (12-hour)
            'd-M-Y H:i',    // 02-Mar-2026 23:59 (24h, no AM/PM)
            'd-M-Y H:i:s',  // 02-Mar-2026 23:59:00 (24h with seconds)
            'd-M-Y g:i A',  // 02-Mar-2026 11:59 PM (12-hour with lowercase)
            'Y-m-d H:i:s', 'Y-m-d', 'd-m-Y H:i:s', 'd-m-Y', 'd/m/Y', 'm/d/Y',
            'd/m/y', 'm/d/y', 'd/m/Y H:i:s', 'm/d/Y H:i:s', 'd/m/y H:i:s', 'm/d/y H:i:s',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $cleanValue);
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Special handling for DD/MM/YY format (like 01/03/20)
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $cleanValue, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            // Convert 2-digit year to 4-digit year (assuming 2000s for 00-99)
            $fullYear = '20'.$year;

            $date = DateTimeImmutable::createFromFormat('Y-m-d', "$fullYear-$month-$day");
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Special handling for format like "02-Mar-2026 23:59 PM"
        if (preg_match('/^(\d{1,2})-([A-Za-z]{3})-(\d{4})\s+(\d{1,2}):(\d{2})(\s+(AM|PM))?$/i', $cleanValue, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            $hour = (int) $matches[4];
            $minute = $matches[5];
            $ampm = isset($matches[7]) ? mb_strtoupper($matches[7]) : null;

            // Convert month name to number
            $monthNum = date('m', strtotime("$day-$month-$year"));
            if ($monthNum === false) {
                return null;
            }

            // Convert to 24-hour format only if AM/PM is present
            if ($ampm === 'PM' && $hour < 12) {
                $hour += 12;
            } elseif ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            }
            // If no AM/PM, assume 24-hour format (don't convert)

            $date = DateTimeImmutable::createFromFormat('Y-m-d H:i', "$year-$monthNum-$day $hour:$minute");
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Try strtotime as fallback
        $timestamp = strtotime($cleanValue);
        if ($timestamp !== false) {
            return date('Y-m-d H:i:s', $timestamp);
        }

        return null;
    }

    private function parseDecimal($value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        // Remove commas and convert to float
        $cleaned = str_replace(',', '', $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function parseFlexibleDecimal($value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        // Clean the value - remove common Excel formatting
        $cleaned = mb_trim((string) $value);

        // Remove commas (thousands separators)
        $cleaned = str_replace(',', '', $cleaned);

        // Remove currency symbols and common Excel number formatting
        $cleaned = preg_replace('/[^\d.\-]/', '', $cleaned);

        // Handle multiple decimal points (Excel sometimes does this)
        if (mb_substr_count($cleaned, '.') > 1) {
            // Keep only the last decimal point
            $parts = explode('.', $cleaned);
            $cleaned = implode('', array_slice($parts, 0, -1)).'.'.end($parts);
        }

        // Try to extract numbers from the string
        preg_match('/[-+]?\d*\.?\d+/', $cleaned, $matches);
        if (isset($matches[0])) {
            $cleaned = $matches[0];
        }

        // Return null if not numeric (don't throw error during preview)
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function parseRequiredDecimal($value, string $fieldName): float
    {
        if ($value === null || $value === '' || $value === '-') {
            throw new InvalidArgumentException("{$fieldName} is required");
        }

        // Clean the value - remove common Excel formatting
        $cleaned = mb_trim((string) $value);

        // Remove commas (thousands separators)
        $cleaned = str_replace(',', '', $cleaned);

        // Remove currency symbols and common Excel number formatting
        $cleaned = preg_replace('/[^\d.\-]/', '', $cleaned);

        // Handle multiple decimal points (Excel sometimes does this)
        if (mb_substr_count($cleaned, '.') > 1) {
            // Keep only the last decimal point
            $parts = explode('.', $cleaned);
            $cleaned = implode('', array_slice($parts, 0, -1)).'.'.end($parts);
        }

        // Check if it's numeric now
        if (! is_numeric($cleaned)) {
            // Try to extract numbers from the string
            preg_match('/[-+]?\d*\.?\d+/', $cleaned, $matches);
            if (isset($matches[0])) {
                $cleaned = $matches[0];
            } else {
                throw new InvalidArgumentException("{$fieldName} must be a valid number. Got: '{$value}'");
            }
        }

        $floatValue = (float) $cleaned;

        // Additional validation
        if ($floatValue < 0) {
            throw new InvalidArgumentException("{$fieldName} cannot be negative");
        }

        return $floatValue;
    }
}
