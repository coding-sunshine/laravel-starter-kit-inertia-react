<?php

declare(strict_types=1);

namespace App\Http\Controllers\Indents;

use App\Actions\CreateIndentAndProvisionRakeAction;
use App\Actions\DeleteIndentAction;
use App\Actions\ProvisionRakeForIndent;
use App\Actions\UpdateStockLedger;
use App\DataTables\IndentDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndentRequest;
use App\Models\Indent;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Services\IndentPdfImporter;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

final class IndentsController extends Controller
{
    /** @var list<string> */
    private const INDENT_STATE_VALUES = [
        'pending',
        'allocated',
        'partial',
        'completed',
        'cancelled',
        'historical_import',
        'submitted',
        'acknowledged',
        'fulfilled',
        'closed',
    ];

    public function importPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $user = $request->user();
        abort_unless($user !== null, 401);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        try {
            $prefill = app(IndentPdfImporter::class)->previewImport(
                $validated['pdf'],
                $user->id,
                $sidingIds
            );

            return response()->json([
                'data' => [
                    'prefill' => $prefill,
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'pdf' => [$e->getMessage()],
                ],
            ], 422);
        } catch (Throwable $e) {
            report($e);

            $message = 'Import failed: '.$e->getMessage();

            return response()->json([
                'message' => $message,
                'errors' => [
                    'pdf' => [$message],
                ],
            ], 422);
        }
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $catalog = $user !== null && $this->hasSectionPermission($user, 'sections.indents.create')
            ? $this->indentsFormCatalog($user)
            : ['sidings' => [], 'power_plants' => []];

        return Inertia::render('indents/index', [
            'tableData' => IndentDataTable::makeTable($request),
            'sidings' => $catalog['sidings'],
            'power_plants' => $catalog['power_plants'],
        ]);
    }

    public function create(Request $request): Response
    {
        // $this->authorize('create', Indent::class);

        $user = $request->user();
        $catalog = $this->indentsFormCatalog($user);

        return Inertia::render('indents/create', [
            'sidings' => $catalog['sidings'],
            'power_plants' => $catalog['power_plants'],
        ]);
    }

    public function store(StoreIndentRequest $request): JsonResponse|RedirectResponse
    {
        // $this->authorize('create', Indent::class);

        $user = $request->user();
        abort_unless($user !== null, 401);

        $rake = app(CreateIndentAndProvisionRakeAction::class)->handle(
            $request,
            $user,
            $request->validated(),
        );

        $successMessage = 'Indent created. Open the rake to continue.';

        if ($request->wantsJson()) {
            session()->flash('success', $successMessage);

            return response()->json([
                'redirect' => route('rakes.show', $rake),
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', $successMessage);
    }

    public function downloadPdf(Indent $indent): \Illuminate\Contracts\Support\Responsable
    {
        $media = $indent->getFirstMedia('indent_pdf')
            ?? $indent->getFirstMedia('indent_confirmation_pdf');
        if (! $media) {
            abort(404, 'No PDF attached to this indent.');
        }

        return $media;
    }

    public function show(Request $request, Indent $indent): Response
    {
        // $this->authorize('view', $indent);

        $indent->load([
            'siding:id,name,code',
        ]);

        // Load rake relationship safely without causing attribute errors
        $rake = Rake::where('indent_id', $indent->id)
            ->select('id', 'rake_number', 'priority_number', 'state')
            ->first();

        $hasPdf = $indent->getFirstMedia('indent_pdf')
            || $indent->getFirstMedia('indent_confirmation_pdf');
        $indent->setAttribute('indent_pdf_download_url', $hasPdf ? route('indents.pdf', $indent) : null);

        $user = $request->user();
        $canDeleteIndent = $user !== null
            && $this->hasSectionPermission($user, 'sections.indents.delete')
            && app(DeleteIndentAction::class)->canDeleteWithRakeEligibility($indent);

        return Inertia::render('indents/show', [
            'indent' => $indent,
            'rake' => $rake,
            'can_delete_indent' => $canDeleteIndent,
        ]);
    }

    public function edit(Request $request, Indent $indent): Response
    {
        // $this->authorize('update', $indent);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        // Backward compatibility: some legacy users only have `users.siding_id`
        // and no rows in the `user_siding` pivot table.
        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $indent->load([
            'siding:id,name,code',
            'rake:id,indent_id,rake_number,priority_number',
        ]);

        $hasPdf = $indent->getFirstMedia('indent_pdf')
            || $indent->getFirstMedia('indent_confirmation_pdf');
        $indent->setAttribute('indent_pdf_download_url', $hasPdf ? route('indents.pdf', $indent) : null);

        $currentStockMt = app(UpdateStockLedger::class)->getCurrentBalance((int) $indent->siding_id);

        return Inertia::render('indents/edit', [
            'indent' => $indent,
            'sidings' => $sidings,
            'currentStockMt' => $currentStockMt,
        ]);
    }

    public function update(Request $request, Indent $indent): JsonResponse|RedirectResponse
    {
        // $this->authorize('update', $indent);

        $indent->loadMissing('rake:id,indent_id');

        if ($request->has('fnr_number')) {
            $rawFnr = $request->input('fnr_number');
            $request->merge([
                'fnr_number' => is_string($rawFnr) && mb_trim($rawFnr) !== '' ? mb_trim($rawFnr) : null,
            ]);
        }

        $validated = $request->validate([
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'indent_number' => ['required'],
            'state' => ['nullable', 'string', Rule::in(self::INDENT_STATE_VALUES)],
            'remarks' => ['nullable', 'string', 'max:65535'],
            'e_demand_reference_id' => ['nullable', 'string', 'max:100'],
            'fnr_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('indents', 'fnr_number')->ignore($indent->id)->whereNull('deleted_at'),
            ],
            'railway_reference_no' => ['nullable', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:100'],
            'expected_loading_date' => ['nullable', 'date'],
            'required_by_date' => ['nullable', 'date'],
            'indent_at' => ['nullable', 'date'],
            'demanded_stock' => ['nullable', 'string', 'max:100'],
            'total_units' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'target_quantity_mt' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'allocated_quantity_mt' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'available_stock_mt' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);

        $indent->siding_id = $validated['siding_id'];
        $indent->indent_number = mb_trim((string) $validated['indent_number']) !== '' ? mb_trim((string) $validated['indent_number']) : null;
        $indent->state = $validated['state'] ?? $indent->state;
        $indent->remarks = $validated['remarks'] ?? null;
        $indent->e_demand_reference_id = $validated['e_demand_reference_id'] ?? null;
        $indent->fnr_number = $validated['fnr_number'] ?? null;
        $indent->railway_reference_no = $validated['railway_reference_no'] ?? null;
        $indent->destination = $validated['destination'] ?? null;
        $indent->expected_loading_date = $validated['expected_loading_date'] ?? null;
        $indent->required_by_date = $validated['required_by_date'] ?? null;
        $indent->demanded_stock = $validated['demanded_stock'] ?? null;
        $indent->total_units = $validated['total_units'] ?? null;
        $indent->target_quantity_mt = $validated['target_quantity_mt'] ?? null;
        $indent->allocated_quantity_mt = $validated['allocated_quantity_mt'] ?? null;
        $indent->available_stock_mt = $validated['available_stock_mt'] ?? null;
        $this->applyIndentAt($indent, $validated['indent_at'] ?? null);
        $indent->updated_by = $request->user()->id;
        $indent->save();

        if ($indent->rake !== null && array_key_exists('rake_serial_number', $validated)) {
            $indent->rake->rake_serial_number = mb_trim((string) $validated['rake_serial_number']);
            $indent->rake->updated_by = $request->user()->id;
            $indent->rake->save();
        }

        $successMessage = 'Indent updated.';

        if ($request->wantsJson()) {
            session()->flash('success', $successMessage);

            return response()->json([
                'redirect' => route('indents.show', $indent),
            ]);
        }

        return to_route('indents.show', $indent)
            ->with('success', $successMessage);
    }

    public function assignRakeNumber(Request $request, Indent $indent): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if (! $user->isSuperAdmin() && ! $user->canAccessSiding($indent->siding_id)) {
            abort(403);
        }

        $validated = $request->validate(
            [
                'rake_serial_number' => ['required', 'string', 'max:100'],
            ],
            [],
            [
                'rake_serial_number' => 'Rake number',
            ],
        );

        $indent->loadMissing('rake:id,indent_id,siding_id,rake_serial_number,loading_date');
        if ($indent->rake === null) {
            return response()->json([
                'message' => 'This e-Demand does not have a linked rake yet.',
                'errors' => [
                    'rake_serial_number' => ['This e-Demand does not have a linked rake yet.'],
                ],
            ], 422);
        }

        if ($indent->rake->loading_date === null) {
            return response()->json([
                'message' => 'Cannot validate rake number uniqueness because loading date is missing.',
                'errors' => [
                    'rake_serial_number' => ['Cannot validate rake number uniqueness because loading date is missing.'],
                ],
            ], 422);
        }

        $reference = Date::parse($indent->rake->loading_date);
        $trimmedSerial = mb_trim((string) $validated['rake_serial_number']);
        $existsInMonth = Rake::query()
            ->where('rake_serial_number', $trimmedSerial)
            ->where('siding_id', $indent->rake->siding_id)
            ->whereYear('loading_date', $reference->year)
            ->whereMonth('loading_date', $reference->month)
            ->whereKeyNot($indent->rake->id)
            ->exists();

        if ($existsInMonth) {
            return response()->json([
                'message' => 'This rake number is already in use for this siding in the loading month.',
                'errors' => [
                    'rake_serial_number' => ['This rake number is already in use for this siding in the loading month.'],
                ],
            ], 422);
        }

        $indent->rake->rake_serial_number = $trimmedSerial;
        $indent->rake->updated_by = $user->id;
        $indent->rake->save();

        return response()->json([
            'message' => 'Rake number updated.',
            'data' => [
                'rake_serial_number' => $indent->rake->rake_serial_number,
            ],
        ]);
    }

    /**
     * Show the form to create a rake from this indent
     */
    public function createRake(Request $request, Indent $indent): Response
    {
        $user = $request->user();
        abort_unless($this->hasStrictSectionPermission($user, 'sections.rakes.create'), 403);

        if (! $user->isSuperAdmin() && ! $user->canAccessSiding($indent->siding_id)) {
            abort(403);
        }

        $indent->load('rake:id,indent_id');
        if ($indent->rake !== null) {
            return to_route('rakes.show', $indent->rake)
                ->with('info', 'This indent already has a rake.');
        }

        try {
            $indentRef = ProvisionRakeForIndent::referenceDateFromIndent($indent);
            $maxPriorityThisMonthOnSiding = Rake::query()
                ->where('siding_id', $indent->siding_id)
                ->whereYear('loading_date', $indentRef->year)
                ->whereMonth('loading_date', $indentRef->month)
                ->max('priority_number');
        } catch (InvalidArgumentException) {
            $maxPriorityThisMonthOnSiding = Rake::query()
                ->where('siding_id', $indent->siding_id)
                ->max('priority_number');
        }

        $nextPriorityNumber = (int) $maxPriorityThisMonthOnSiding + 1;

        $powerPlants = PowerPlant::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'code']);

        $prefillDestinationCode = $this->resolveDestinationCodeFromIndent($indent->destination);

        return Inertia::render('rakes/create-from-indent', [
            'indent' => $indent->load('siding:id,name,code'),
            'next_priority_number' => $nextPriorityNumber,
            'power_plants' => $powerPlants,
            'prefill_destination_code' => $prefillDestinationCode,
        ]);
    }

    /**
     * Store a new rake created from an indent
     */
    public function storeRakeFromIndent(Request $request, Indent $indent): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->hasStrictSectionPermission($user, 'sections.rakes.create'), 403);

        if (! $user->isSuperAdmin() && ! $user->canAccessSiding($indent->siding_id)) {
            abort(403);
        }

        $indent->load('rake:id,indent_id');
        if ($indent->rake !== null) {
            return to_route('rakes.show', $indent->rake)
                ->with('info', 'This indent already has a rake.');
        }

        $indentReference = null;
        try {
            $indentReference = ProvisionRakeForIndent::referenceDateFromIndent($indent);
        } catch (InvalidArgumentException) {
            $indentReference = null;
        }

        $validated = $request->validate([
            'rake_serial_number' => ['required', 'string', 'max:100'],
            'rake_number' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($indent, $indentReference) {
                    $trimmed = $value !== null && mb_trim((string) $value) !== '' ? mb_trim((string) $value) : null;
                    if ($trimmed === null) {
                        return;
                    }
                    if ($indentReference === null) {
                        $fail('Cannot validate rake number: indent must have an indent date or expected loading date.');

                        return;
                    }
                    $existsInMonth = Rake::query()
                        ->where('rake_number', $trimmed)
                        ->where('siding_id', $indent->siding_id)
                        ->whereYear('loading_date', $indentReference->year)
                        ->whereMonth('loading_date', $indentReference->month)
                        ->exists();
                    if ($existsInMonth) {
                        $fail('This rake number is already in use for this siding in the indent month.');
                    }
                },
            ],
            'rake_priority_number' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, Closure $fail) use ($indent, $indentReference) {
                    if (! is_numeric($value)) {
                        return;
                    }
                    $num = (int) $value;
                    if ($indentReference === null) {
                        $fail('Cannot validate rake priority: indent must have an indent date or expected loading date.');

                        return;
                    }
                    $existsInMonth = Rake::query()
                        ->where('priority_number', $num)
                        ->where('siding_id', $indent->siding_id)
                        ->whereYear('loading_date', $indentReference->year)
                        ->whereMonth('loading_date', $indentReference->month)
                        ->exists();
                    if ($existsInMonth) {
                        $fail('This rake priority number is already in use for this siding in the indent month.');
                    }
                },
            ],
            'loading_date' => ['required', 'date'],
            'rake_type' => ['required', 'string', 'max:50'],
            'wagon_count' => ['required', 'integer', 'min:0'],
            'destination_code' => [
                'required',
                'string',
                'max:20',
                Rule::exists('power_plants', 'code')->where('is_active', true),
            ],
            'rr_expected_date' => ['nullable', 'date'],
            'placement_time' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        // Create rake from indent with form data
        $rake = new Rake;
        $rake->indent_id = $indent->id;
        $rake->siding_id = $indent->siding_id; // Use indent's siding
        $rake->rake_number = $validated['rake_number'];
        $rake->rake_serial_number = mb_trim((string) $validated['rake_serial_number']);
        $rake->priority_number = isset($validated['rake_priority_number']) ? (int) $validated['rake_priority_number'] : ((int) Rake::query()->max('priority_number') + 1);
        $rake->loading_date = isset($validated['loading_date']) ? $validated['loading_date'] : null;
        $rake->rake_type = $validated['rake_type'] ?? null;
        $rake->wagon_count = $validated['wagon_count'] ?? 0;
        $rake->loaded_weight_mt = 0;
        $rake->predicted_weight_mt = null;
        $rake->state = 'pending';
        $rake->loading_free_minutes = (int) config('rrmcs.default_free_time_minutes', 180);
        $rake->rr_expected_date = $validated['rr_expected_date'] ?? null;
        $rake->placement_time = ! empty($validated['placement_time'] ?? null)
            ? new DateTimeImmutable($validated['placement_time'])
            : null;

        $powerPlant = PowerPlant::query()
            ->where('code', $validated['destination_code'])
            ->where('is_active', true)
            ->firstOrFail();
        $rake->destination = $powerPlant->name;
        $rake->destination_code = $powerPlant->code;

        $rake->created_by = $user->id;
        $rake->updated_by = $user->id;
        $rake->save();

        // Create wagons based on wagon count
        $wagonCount = (int) ($validated['wagon_count'] ?? 0);
        if ($wagonCount > 0) {
            for ($i = 1; $i <= $wagonCount; $i++) {
                $wagon = new Wagon;
                $wagon->rake_id = $rake->id;
                $wagon->wagon_number = "W{$i}"; // W1, W2, W3, etc.
                $wagon->wagon_sequence = $i;
                $wagon->state = 'pending';
                $wagon->save();
            }
        }

        $indent->update([
            'state' => 'completed',
            'updated_by' => $user->id,
        ]);

        return to_route('rakes.show', $rake)
            ->with('success', 'Rake created from completed indent successfully.');
    }

    public function destroy(Request $request, Indent $indent, DeleteIndentAction $deleteIndent): RedirectResponse
    {
        try {
            $deleteIndent->handle($indent);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return to_route('indents.index')
            ->with('success', 'Indent deleted.');
    }

    /**
     * @return array{sidings: \Illuminate\Database\Eloquent\Collection<int, Siding>, power_plants: \Illuminate\Database\Eloquent\Collection<int, PowerPlant>}
     */
    private function indentsFormCatalog(\App\Models\User $user): array
    {
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $powerPlants = PowerPlant::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'code']);

        return [
            'sidings' => $sidings,
            'power_plants' => $powerPlants,
        ];
    }

    private function applyIndentAt(Indent $indent, mixed $value): void
    {
        if ($value === null || $value === '') {
            $indent->indent_date = null;
            $indent->indent_time = null;

            return;
        }

        $parsed = Date::parse($value);
        $indent->indent_date = $parsed;
        $indent->indent_time = $parsed;
    }

    private function hasSectionPermission(\App\Models\User $user, string $permission): bool
    {
        if ($user->can('bypass-permissions')) {
            return true;
        }

        if (\App\Services\TenantContext::check() && $user->canInCurrentOrganization($permission)) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }

    private function hasStrictSectionPermission(\App\Models\User $user, string $permission): bool
    {
        if (\App\Services\TenantContext::check() && $user->canInCurrentOrganization($permission)) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }

    private function resolveDestinationCodeFromIndent(?string $destination): ?string
    {
        if ($destination === null || mb_trim($destination) === '') {
            return null;
        }

        $normalized = mb_strtolower(mb_trim($destination));

        $exactCode = PowerPlant::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(code) = ?', [$normalized])
            ->value('code');

        if ($exactCode !== null) {
            return (string) $exactCode;
        }

        $exactName = PowerPlant::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->value('code');

        if ($exactName !== null) {
            return (string) $exactName;
        }

        $likeMatch = PowerPlant::query()
            ->where('is_active', true)
            ->where(function ($query) use ($normalized): void {
                $query->whereRaw('LOWER(name) LIKE ?', ['%'.$normalized.'%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%'.$normalized.'%']);
            })
            ->orderBy('name')
            ->value('code');

        return $likeMatch !== null ? (string) $likeMatch : null;
    }
}
