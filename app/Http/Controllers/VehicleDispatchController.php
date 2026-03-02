<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVehicleDispatchRequest;
use App\Models\Siding;
use App\Models\VehicleDispatch;
use DateTimeImmutable;
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

final class VehicleDispatchController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $currentSiding = session('current_siding');

        $query = VehicleDispatch::with(['siding', 'creator'])
            ->when($currentSiding, fn ($q) => $q->forSiding($currentSiding->id))
            ->when($request->date, fn ($q) => $q->forDate($request->date))
            ->when($request->shift, fn ($q) => $q->forShift($request->shift))
            ->when($request->permit_no, fn ($q) => $q->byPermitNo($request->permit_no))
            ->when($request->truck_regd_no, fn ($q) => $q->byTruckRegdNo($request->truck_regd_no))
            ->orderBy('issued_on', 'desc')
            ->orderBy('created_at', 'desc');
            
        $vehicleDispatches = $query->paginate(25);

        $shifts = ['Morning', 'Evening', 'Night'];
        $availableDates = VehicleDispatch::selectRaw('DATE(issued_on) as date')
            ->whereNotNull('issued_on')
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date');

        // Get available sidings for import dropdown
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('VehicleDispatch/Index', [
            'vehicleDispatches' => $vehicleDispatches,
            'filters' => $request->only(['date', 'shift', 'permit_no', 'truck_regd_no']),
            'shifts' => $shifts,
            'availableDates' => $availableDates,
            'currentSiding' => $currentSiding,
            'sidings' => $sidings,
            'preview_data' => $request->session()->get('preview_data', []),
            'import_siding_id' => $request->session()->get('import_siding_id'),
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ]);
    }

    public function update(UpdateVehicleDispatchRequest $request, VehicleDispatch $vehicleDispatch): RedirectResponse
    {
        $vehicleDispatch->update($request->validated());

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
            'siding_id' => 'required|exists:sidings,id',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $request->input('data');
        $sidingId = $request->input('siding_id');

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
                $parsedData = $this->parseVehicleDispatchRow($row, $sidingId);
                $parsedRows[] = $parsedData;
            } catch (Exception $e) {
                $errors[] = 'Row '.($index + 2).': '.$e->getMessage();
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'import_errors' => $errors,
            ]);
        }

        // Redirect back to index with preview data in session (keeps URL as /vehicle-dispatch)
        return redirect()
            ->route('vehicle-dispatch.index', $request->only(['date', 'shift', 'permit_no', 'truck_regd_no']))
            ->with('preview_data', $parsedRows)
            ->with('import_siding_id', $sidingId);
    }

    public function saveImport(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'siding_id' => 'required|exists:sidings,id',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $request->input('data');
        $sidingId = $request->input('siding_id');

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($data, $sidingId, &$imported, &$errors) {
            foreach ($data as $index => $row) {
                try {
                    // Validate required fields before saving
                    $this->validateVehicleDispatchData($row, $sidingId);

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

        return redirect()->route('vehicle-dispatch.index')
            ->with('success', "Successfully imported {$imported} vehicle dispatch records.");
    }

    private function getVehicleDispatches($request)
    {
        $currentSiding = $this->getCurrentSiding($request);
        $query = VehicleDispatch::query()
            ->with(['siding', 'creator'])
            ->when($currentSiding, fn ($q) => $q->where('siding_id', $currentSiding->id))
            ->when($request->date, fn ($q) => $q->whereDate('issued_on', $request->date))
            ->when($request->shift, fn ($q) => $q->where('shift', $request->shift))
            ->when($request->permit_no, fn ($q) => $q->byPermitNo($request->permit_no))
            ->when($request->truck_regd_no, fn ($q) => $q->byTruckRegdNo($request->truck_regd_no))
            ->orderBy('issued_on', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate(25);
    }

    private function getCurrentSiding($request)
    {
        return session('current_siding');
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

    private function validateVehicleDispatchData(array $row, int $sidingId): void
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
    }

    private function parsePasteData(string $data): array
    {
        // Decode HTML entities (e.g. &nbsp; when copying from web tables)
        $data = html_entity_decode($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
            'serial', 'permit', 'pass', 'truck', 'mineral', 'weight', 'source',
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

    private function parseVehicleDispatchRow(array $row, int $sidingId): array
    {
        // Format A matches: S.NO|REF|Permit|Pass|Stack|IssuedOn|Truck|Mineral|MinType|Weight|Source|Dest|Consignee|CheckGate|Distance|Shift
        $formatA = $this->mapFormatA($row, $sidingId);
        if ($this->hasValidRequiredFields($formatA)) {
            return $formatA;
        }

        $formatB = $this->mapFormatB($row, $sidingId);
        if ($this->hasValidRequiredFields($formatB)) {
            return $formatB;
        }

        $formatC = $this->mapFormatC($row, $sidingId);
        if ($this->hasValidRequiredFields($formatC)) {
            return $formatC;
        }

        return $this->mapWithWeightScan($row, $sidingId);
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
    private function mapFormatA(array $row, int $sidingId): array
    {
        return [
            'siding_id' => $sidingId,
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
            'distance_km' => $this->parseNullableInt($row[14] ?? null),
            'shift' => $this->parseNullableString($row[15] ?? null),
            'created_by' => auth()->id(),
        ];
    }

    /** Format B: 6=Mineral, 7=MinType, 8=MinWeight, 15=Truck */
    private function mapFormatB(array $row, int $sidingId): array
    {
        return [
            'siding_id' => $sidingId,
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
            'distance_km' => $this->parseNullableInt($row[13] ?? null),
            'shift' => $this->parseNullableString($row[14] ?? null),
            'truck_regd_no' => $this->parseNullableString($row[15] ?? null),
            'created_by' => auth()->id(),
        ];
    }

    /** Format C: 9 columns - 6=Truck, 7=Mineral, 8=MinWeight (no mineral type) */
    private function mapFormatC(array $row, int $sidingId): array
    {
        return [
            'siding_id' => $sidingId,
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

    /** Fallback: scan cols 6-11 for first valid weight, infer other fields */
    private function mapWithWeightScan(array $row, int $sidingId): array
    {
        $weightCol = null;
        for ($i = 6; $i <= 11; $i++) {
            $w = $this->parseFlexibleDecimal($row[$i] ?? null);
            if ($w !== null && $w > 0 && $w < 10000) {
                $weightCol = $i;
                break;
            }
        }

        // If we found weight at col 8
        if ($weightCol === 8) {
            return count($row) <= 9
                ? $this->mapFormatC($row, $sidingId)
                : $this->mapFormatB($row, $sidingId);
        }
        if ($weightCol === 9) {
            return $this->mapFormatA($row, $sidingId);
        }
        if ($weightCol === 7) {
            // Mineral at 6, Weight at 7 (6 used for both truck and mineral when only 8 cols)
            return [
                'siding_id' => $sidingId,
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
        return $this->mapFormatA($row, $sidingId);
    }

    private function createVehicleDispatchFromRow(array $row, int $sidingId): VehicleDispatch
    {
        $data = $this->parseVehicleDispatchRow($row, $sidingId);

        return VehicleDispatch::create($data);
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

        // Try to parse various date formats
        $formats = [
            'Y-m-d H:i:s', 'Y-m-d', 'd-m-Y H:i:s', 'd-m-Y', 'd/m/Y', 'm/d/Y',
            'd/m/y', 'm/d/y', 'd/m/Y H:i:s', 'm/d/Y H:i:s', 'd/m/y H:i:s', 'm/d/y H:i:s',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, mb_trim($value));
            if ($date) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        // Special handling for DD/MM/YY format (like 01/03/20)
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', mb_trim($value), $matches)) {
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

        // Try strtotime as fallback
        $timestamp = strtotime($value);
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
