<?php

declare(strict_types=1);

namespace App\Http\Controllers\RailwayReceipts;

use App\Actions\ProcessRrDocument;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\Siding;
use App\Services\SidingContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RrDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);

        $query = RrDocument::query()
            ->with('rake.siding:id,name,code')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->latest('rr_received_date');

        if ($request->filled('rake_id')) {
            $query->where('rake_id', $request->input('rake_id'));
        }
        if ($request->filled('siding_id')) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $request->input('siding_id')));
        }

        $rrDocuments = $query->paginate(15)->withQueryString();
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('railway-receipts/index', [
            'rrDocuments' => $rrDocuments,
            'sidings' => $sidings,
        ]);
    }

    public function show(Request $request, RrDocument $rrDocument): Response
    {
        $this->authorize('view', $rrDocument);

        $rrDocument->load('rake.siding:id,name,code');

        return Inertia::render('railway-receipts/show', [
            'rrDocument' => $rrDocument,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', RrDocument::class);
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);
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
            'currentSidingId' => SidingContext::id(),
        ]);
    }

    public function store(Request $request, ProcessRrDocument $processRrDocument): RedirectResponse
    {
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
        $rake = Rake::findOrFail($validated['rake_id']);
        $this->authorize('update', $rake);

        $doc = RrDocument::create([
            'rake_id' => $validated['rake_id'],
            'rr_number' => $validated['rr_number'],
            'rr_received_date' => $validated['rr_received_date'],
            'rr_weight_mt' => $validated['rr_weight_mt'] ?? null,
            'fnr' => $validated['fnr'] ?? null,
            'from_station_code' => $validated['from_station_code'] ?? null,
            'to_station_code' => $validated['to_station_code'] ?? null,
            'freight_total' => $validated['freight_total'] ?? null,
            'document_status' => $validated['document_status'] ?? 'received',
            'created_by' => $request->user()->id,
        ]);
        $message = 'RR document saved.';
        if ($request->hasFile('pdf')) {
            $doc->addMediaFromRequest('pdf')->toMediaCollection('rr_pdf');
            $extracted = $processRrDocument->extractFromUpload($request->file('pdf'));
            if ($extracted !== null && $extracted !== []) {
                $update = [];
                if (! empty($extracted['rr_number'])) {
                    $update['rr_number'] = $extracted['rr_number'];
                }
                if (isset($extracted['rr_weight_mt'])) {
                    $update['rr_weight_mt'] = $extracted['rr_weight_mt'];
                }
                if (! empty($extracted['rr_received_date'])) {
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

        return redirect()
            ->route('railway-receipts.index')
            ->with('success', $message);
    }

    public function update(Request $request, RrDocument $rrDocument): RedirectResponse
    {
        $this->authorize('update', $rrDocument->rake);

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
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->back()
            ->with('success', 'RR document updated.');
    }
}
