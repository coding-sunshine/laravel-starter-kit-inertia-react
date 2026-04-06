<?php

declare(strict_types=1);

namespace App\Http\Controllers\RailwayReceipts;

use App\Actions\DeleteRrDocumentAction;
use App\DataTables\RrDocumentDataTable;
use App\Http\Controllers\Controller;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\Siding;
use App\Services\Railway\RrImportService;
use App\Services\Railway\RrParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use InvalidArgumentException;
use Throwable;

final class RrDocumentController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.view'), 403);

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

        return Inertia::render('railway-receipts/index', [
            'tableData' => RrDocumentDataTable::makeTable($request),
            'sidings' => $sidings,
            'can_upload_rr' => $this->hasSectionPermission($user, 'sections.railway_receipts.upload'),
        ]);
    }

    public function upload(
        Request $request,
        RrParserService $parser,
        RrImportService $rrImportService,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.upload'), 403);

        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'power_plant_id' => ['nullable', 'integer', 'exists:power_plants,id'],
            'rake_id' => ['nullable', 'integer', 'exists:rakes,id'],
        ]);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        // Backward compatibility: some legacy users only have `users.siding_id`
        // and no rows in the `user_siding` pivot table.
        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }
        $sidingId = $request->input('siding_id') ?? $sidingIds[0] ?? null;
        $powerPlantId = $request->input('power_plant_id') ?? PowerPlant::query()->orderBy('id')->value('id');
        $rakeId = $request->input('rake_id');

        if ($sidingId === null) {
            return back()->withErrors(['pdf' => 'No siding available. Create a siding first.']);
        }
        if ($powerPlantId === null) {
            return back()->withErrors(['pdf' => 'No power plant available. Create a power plant first.']);
        }

        $rake = null;
        if ($rakeId !== null && $rakeId !== '') {
            $rake = Rake::query()->find((int) $rakeId);
            if ($rake === null) {
                return back()->withErrors(['pdf' => 'Selected rake is invalid or no longer available.']);
            }
            if (! in_array($rake->siding_id, $sidingIds, true)) {
                return back()->withErrors(['pdf' => 'You are not allowed to attach RR documents to the selected rake.']);
            }
        }

        try {
            $parsed = $parser->parse($request->file('pdf'));
            $validated = [
                'pdf' => $request->file('pdf'),
                'siding_id' => $sidingId,
                'power_plant_id' => $powerPlantId,
            ];
            $rrDocument = $rrImportService->importSnapshotOnly($parsed, $request, $validated, $rake, null);

            return to_route('railway-receipts.show', $rrDocument)
                ->with('success', 'RR document uploaded and parsed successfully.');
        } catch (InvalidArgumentException $e) {
            Log::warning('RR upload validation failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['pdf' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('RR upload failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->withErrors(['pdf' => 'Failed to process Railway Receipt. Please ensure the PDF is valid and try again.']);
        }
    }

    public function downloadPdf(RrDocument $rrDocument): \Illuminate\Contracts\Support\Responsable
    {
        $user = request()->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.view'), 403);
        abort_unless($this->canAccessRrDocument($user, $rrDocument), 403);

        $media = $rrDocument->getFirstMedia('rr_pdf');
        if (! $media) {
            abort(404, 'No PDF attached to this Railway Receipt.');
        }

        return $media;
    }

    public function show(Request $request, RrDocument $rrDocument): \Inertia\Response
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.view'), 403);
        abort_unless($this->canAccessRrDocument($user, $rrDocument), 403);

        $rrDocument->load([
            'rake.siding:id,name,code',
            'rake.wagons',
            'rake.rakeCharges:id,rake_id,diverrt_destination_id,charge_type,amount,is_actual_charges,data_source,remarks',
            'rake.appliedPenalties.penaltyType:id,code,name,calculation_type',
            'rake.appliedPenalties.wagon:id,wagon_number,overload_weight_mt',
            'rrCharges',
            'wagonSnapshots',
            'penaltySnapshots',
        ]);

        $fromSiding = $rrDocument->from_station_code
            ? Siding::query()->where('code', $rrDocument->from_station_code)->first(['id', 'name', 'code'])
            : null;

        $toPowerPlant = $rrDocument->to_station_code
            ? PowerPlant::query()->where('code', $rrDocument->to_station_code)->first(['id', 'name', 'code'])
            : null;

        $canDeleteRr = $this->hasStrictSectionPermission($user, 'sections.railway_receipts.delete');

        $hasPdf = $rrDocument->getFirstMedia('rr_pdf') !== null;
        $rrDocument->setAttribute('rr_pdf_download_url', $hasPdf ? route('railway-receipts.pdf', $rrDocument) : null);
        $ledgerCharges = $rrDocument->rake?->rakeCharges
            ?->filter(fn ($charge): bool => $charge->is_actual_charges
                && (int) $charge->diverrt_destination_id === (int) $rrDocument->diverrt_destination_id)
            ->map(fn ($charge): array => [
                'id' => $charge->id,
                'charge_type' => $charge->charge_type,
                'amount' => $charge->amount,
                'data_source' => $charge->data_source,
                'remarks' => $charge->remarks,
            ])
            ->values()
            ->all() ?? [];
        $rrDocument->setAttribute('rake_charges_ledger', $ledgerCharges);

        return Inertia::render('railway-receipts/show', [
            'rrDocument' => $rrDocument,
            'fromSiding' => $fromSiding,
            'toPowerPlant' => $toPowerPlant,
            'can_download_rr' => $this->hasSectionPermission($user, 'sections.railway_receipts.view'),
            'can_delete_rr' => $canDeleteRr,
        ]);
    }

    public function rakesForMonth(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.view'), 403);

        $month = (string) $request->query('month', '');

        try {
            $start = $month !== ''
                ? \Illuminate\Support\Facades\Date::parse($month.'-01')->startOfMonth()
                : now()->startOfMonth();
        } catch (Throwable) {
            $start = now()->startOfMonth();
        }

        $end = $start->copy()->endOfMonth();

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        // Backward compatibility: some legacy users only have `users.siding_id`
        // and no rows in the `user_siding` pivot table.
        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }

        $rakes = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('data_source', 'system')
            ->whereNotNull('loading_date')
            ->whereBetween('loading_date', [$start->toDateString(), $end->toDateString()])
            ->whereDoesntHave('rrDocument')
            ->whereDoesntHave('rakeWeighments', static function ($query): void {
                $query->whereNotNull('pdf_file_path');
            })
            ->with('siding:id,name,code')
            ->orderByDesc('loading_date')
            ->orderBy('rake_number')
            ->limit(500)
            ->get([
                'id',
                'rake_number',
                'rr_actual_date',
                'loading_date',
                'siding_id',
                'priority_number',
                'destination',
                'destination_code',
            ]);

        $data = $rakes->map(function (Rake $rake): array {
            $prefill = $this->rakeManualWeighmentPrefillFromRake($rake);

            return [
                'id' => $rake->id,
                'rake_number' => $rake->rake_number,
                'rr_actual_date' => $rake->rr_actual_date?->toDateString(),
                'loading_date' => $rake->loading_date?->toDateString(),
                'siding' => $rake->siding
                    ? [
                        'name' => $rake->siding->name,
                        'code' => $rake->siding->code,
                    ]
                    : null,
                'from_station' => $prefill['from_station'],
                'to_station' => $prefill['to_station'],
                'priority_number' => $prefill['priority_number'],
            ];
        })->values();

        return response()->json([
            'data' => $data,
        ]);
    }

    public function create(Request $request): \Inertia\Response
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.upload'), 403);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->sidings()->get()->pluck('id')->all();

        // Backward compatibility: some legacy users only have `users.siding_id`
        // and no rows in the `user_siding` pivot table.
        if (! $user->isSuperAdmin() && $sidingIds === [] && $user->siding_id !== null) {
            $sidingIds = [(int) $user->siding_id];
        }
        $rakes = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->orderBy('rake_number')
            ->get(['id', 'rake_number', 'siding_id']);
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $preselectedRakeId = $request->input('rake_id');

        return Inertia::render('railway-receipts/create', [
            'rakes' => $rakes,
            'sidings' => $sidings,
            'preselectedRakeId' => $preselectedRakeId ? (int) $preselectedRakeId : null,
        ]);
    }

    public function store(Request $request, ProcessRrDocument $processRrDocument): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.upload'), 403);

        $validated = $request->validate([
            'rake_id' => ['required', 'integer', 'exists:rakes,id'],
            'rr_number' => ['required', 'string', 'max:50', 'unique:rr_documents,rr_number'],
            'rr_received_date' => ['required', 'date'],
            'rr_weight_mt' => ['nullable', 'numeric', 'min:0'],
            'fnr' => ['nullable', 'string', 'max:50'],
            'from_station_code' => ['nullable', 'string', 'max:20'],
            'to_station_code' => ['nullable', 'string', 'max:20'],
            'freight_total' => ['nullable', 'numeric', 'min:0'],
            'document_status' => ['nullable', 'string', 'in:received,verified,discrepancy'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);
        $rake = Rake::query()->findOrFail($validated['rake_id']);
        // $this->authorize('update', $rake);

        $doc = RrDocument::query()->create([
            'rake_id' => $validated['rake_id'],
            'rr_number' => $validated['rr_number'],
            'rr_received_date' => $validated['rr_received_date'],
            'rr_weight_mt' => $validated['rr_weight_mt'] ?? null,
            'fnr' => $validated['fnr'] ?? null,
            'from_station_code' => $validated['from_station_code'] ?? null,
            'to_station_code' => $validated['to_station_code'] ?? null,
            'freight_total' => $validated['freight_total'] ?? null,
            'document_status' => $validated['document_status'] ?? 'received',
            'created_by' => $user->id,
        ]);
        $message = 'RR document saved.';
        if ($request->hasFile('pdf')) {
            $doc->addMediaFromRequest('pdf')->toMediaCollection('rr_pdf');
            $extracted = $processRrDocument->extractFromUpload($request->file('pdf'));
            if ($extracted !== null && $extracted !== []) {
                $update = [];
                if (isset($extracted['rr_number']) && ($extracted['rr_number'] !== '' && $extracted['rr_number'] !== '0')) {
                    $update['rr_number'] = $extracted['rr_number'];
                }
                if (isset($extracted['rr_weight_mt'])) {
                    $update['rr_weight_mt'] = $extracted['rr_weight_mt'];
                }
                if (isset($extracted['rr_received_date']) && ($extracted['rr_received_date'] !== '' && $extracted['rr_received_date'] !== '0')) {
                    $update['rr_received_date'] = $extracted['rr_received_date'];
                }
                if (array_key_exists('fnr', $extracted)) {
                    $update['fnr'] = $extracted['fnr'];
                }
                if (array_key_exists('from_station_code', $extracted)) {
                    $update['from_station_code'] = $extracted['from_station_code'];
                }
                if (array_key_exists('to_station_code', $extracted)) {
                    $update['to_station_code'] = $extracted['to_station_code'];
                }
                if (array_key_exists('freight_total', $extracted)) {
                    $update['freight_total'] = $extracted['freight_total'];
                }
                if (! empty($extracted['rr_details']) && is_array($extracted['rr_details'])) {
                    $update['rr_details'] = $extracted['rr_details'];
                }
                if ($update !== []) {
                    $doc->update($update);
                    $message = 'RR document saved. Details were auto-filled from the PDF; please verify.';
                }
            } else {
                $message = 'RR document saved. PDF attached. Auto-extract is not configured or failed; please verify details manually.';
            }
        }

        return to_route('railway-receipts.index')
            ->with('success', $message);
    }

    public function update(Request $request, RrDocument $rrDocument): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.upload'), 403);
        abort_unless($this->canAccessRrDocument($user, $rrDocument), 403);

        $validated = $request->validate([
            'rr_number' => ['required', 'string', 'max:50', 'unique:rr_documents,rr_number,'.$rrDocument->id],
            'rr_received_date' => ['required', 'date'],
            'rr_weight_mt' => ['nullable', 'numeric', 'min:0'],
            'fnr' => ['nullable', 'string', 'max:50'],
            'from_station_code' => ['nullable', 'string', 'max:20'],
            'to_station_code' => ['nullable', 'string', 'max:20'],
            'freight_total' => ['nullable', 'numeric', 'min:0'],
            'document_status' => ['nullable', 'string', 'in:received,verified,discrepancy'],
            'has_discrepancy' => ['nullable', 'boolean'],
            'discrepancy_details' => ['nullable', 'string', 'max:2000'],
        ]);

        $rrDocument->update([
            ...$validated,
            'updated_by' => $user->id,
        ]);

        return back()
            ->with('success', 'RR document updated.');
    }

    public function destroy(Request $request, RrDocument $rrDocument, DeleteRrDocumentAction $deleteRrDocument): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->hasStrictSectionPermission($user, 'sections.railway_receipts.delete'), 403);
        abort_unless($this->canAccessRrDocument($user, $rrDocument), 403);

        $deleteRrDocument->handle($rrDocument);

        return to_route('railway-receipts.index')
            ->with('success', 'Railway receipt deleted.');
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
        return $user->hasPermissionTo($permission);
    }

    private function canAccessRrDocument(\App\Models\User $user, RrDocument $rrDocument): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Historical RR documents may exist without a linked rake. In that case, only the uploader can view/download/delete.
        if ($rrDocument->rake_id === null) {
            return (int) $rrDocument->created_by === (int) $user->id;
        }

        $sidingId = $rrDocument->rake()->value('siding_id');
        if ($sidingId === null) {
            return false;
        }

        return $user->canAccessSiding((int) $sidingId);
    }

    /**
     * @return array{from_station: string|null, to_station: string|null, priority_number: string|null}
     */
    private function rakeManualWeighmentPrefillFromRake(Rake $rake): array
    {
        $rake->loadMissing('siding');

        $from = null;
        if ($rake->siding !== null) {
            $code = mb_trim((string) $rake->siding->code);
            $name = mb_trim((string) $rake->siding->name);
            if ($code !== '' && $name !== '') {
                $from = $code.' — '.$name;
            } elseif ($name !== '') {
                $from = $name;
            } elseif ($code !== '') {
                $from = $code;
            }
        }

        $dest = mb_trim((string) ($rake->destination ?? ''));
        $destCode = mb_trim((string) ($rake->destination_code ?? ''));
        $to = $dest !== '' ? $dest : ($destCode !== '' ? $destCode : null);

        return [
            'from_station' => $from,
            'to_station' => $to,
            'priority_number' => $rake->priority_number !== null ? (string) $rake->priority_number : null,
        ];
    }
}
