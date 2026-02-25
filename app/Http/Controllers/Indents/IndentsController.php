<?php

declare(strict_types=1);

namespace App\Http\Controllers\Indents;

use App\Http\Controllers\Controller;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class IndentsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $indents = Indent::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->latest('indent_date')
            ->paginate(15)
            ->withQueryString();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('indents/index', [
            'indents' => $indents,
            'sidings' => $sidings,
        ]);
    }

    public function create(Request $request): Response
    {
        // $this->authorize('create', Indent::class);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('indents/create', [
            'sidings' => $sidings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // $this->authorize('create', Indent::class);

        $validated = $request->validate([
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'indent_number' => ['required', 'string', 'max:20', 'unique:indents,indent_number'],
            'target_quantity_mt' => ['required', 'numeric', 'min:0'],
            'allocated_quantity_mt' => ['nullable', 'numeric', 'min:0'],
            'state' => ['nullable', 'string', 'in:pending,allocated,partial,completed,cancelled'],
            'indent_date' => ['required', 'date'],
            'required_by_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'e_demand_reference_id' => ['nullable', 'string', 'max:100'],
            'fnr_number' => ['nullable', 'string', 'max:50'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $indent = new Indent;
        $indent->siding_id = $validated['siding_id'];
        $indent->indent_number = $validated['indent_number'];
        $indent->target_quantity_mt = $validated['target_quantity_mt'];
        $indent->allocated_quantity_mt = $validated['allocated_quantity_mt'] ?? 0;
        $indent->state = $validated['state'] ?? 'pending';
        $indent->indent_date = $validated['indent_date'];
        $indent->required_by_date = $validated['required_by_date'] ?? null;
        $indent->remarks = $validated['remarks'] ?? null;
        $indent->e_demand_reference_id = $validated['e_demand_reference_id'] ?? null;
        $indent->fnr_number = $validated['fnr_number'] ?? null;
        $indent->created_by = $request->user()->id;
        $indent->updated_by = $request->user()->id;
        $indent->save();

        if ($request->hasFile('pdf')) {
            $indent->addMediaFromRequest('pdf')->toMediaCollection('indent_confirmation_pdf');
        }

        return to_route('indents.show', $indent)
            ->with('success', 'Indent created.');
    }

    public function show(Request $request, Indent $indent): Response
    {
        // $this->authorize('view', $indent);

        $indent->load([
            'siding:id,name,code',
        ]);

        // Load rake relationship safely without causing attribute errors
        $rake = Rake::where('indent_id', $indent->id)
            ->select('id', 'rake_number', 'state')
            ->first();

        return Inertia::render('indents/show', [
            'indent' => $indent,
            'rake' => $rake,
        ]);
    }

    public function edit(Request $request, Indent $indent): Response
    {
        // $this->authorize('update', $indent);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $indent->load('siding:id,name,code');

        return Inertia::render('indents/edit', [
            'indent' => $indent,
            'sidings' => $sidings,
        ]);
    }

    public function update(Request $request, Indent $indent): RedirectResponse
    {
        // $this->authorize('update', $indent);

        $validated = $request->validate([
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'indent_number' => ['required', 'string', 'max:20', 'unique:indents,indent_number,'.$indent->id],
            'target_quantity_mt' => ['required', 'numeric', 'min:0'],
            'allocated_quantity_mt' => ['nullable', 'numeric', 'min:0'],
            'state' => ['nullable', 'string', 'in:pending,allocated,partial,completed,cancelled'],
            'indent_date' => ['required', 'date'],
            'required_by_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'e_demand_reference_id' => ['nullable', 'string', 'max:100'],
            'fnr_number' => ['nullable', 'string', 'max:50'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $indent->siding_id = $validated['siding_id'];
        $indent->indent_number = $validated['indent_number'];
        $indent->target_quantity_mt = $validated['target_quantity_mt'];
        $indent->allocated_quantity_mt = $validated['allocated_quantity_mt'] ?? 0;
        $indent->state = $validated['state'] ?? $indent->state;
        $indent->indent_date = $validated['indent_date'];
        $indent->required_by_date = $validated['required_by_date'] ?? null;
        $indent->remarks = $validated['remarks'] ?? null;
        $indent->e_demand_reference_id = $validated['e_demand_reference_id'] ?? null;
        $indent->fnr_number = $validated['fnr_number'] ?? null;
        $indent->updated_by = $request->user()->id;
        $indent->save();

        if ($request->hasFile('pdf')) {
            $indent->clearMediaCollection('indent_confirmation_pdf');
            $indent->addMediaFromRequest('pdf')->toMediaCollection('indent_confirmation_pdf');
        }

        return to_route('indents.show', $indent)
            ->with('success', 'Indent updated.');
    }

    /**
     * Show the form to create a rake from this indent
     */
    public function createRake(Request $request, Indent $indent): Response
    {
        // $this->authorize('createRake', $indent);

        // Check if rake already exists for this indent
        if ($indent->rake) {
            return to_route('indents.show', $indent)
                ->with('error', 'A rake already exists for this indent.');
        }

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('rakes/create-from-indent', [
            'indent' => $indent->load('siding:id,name,code'),
            'sidings' => $sidings,
        ]);
    }

    /**
     * Store a new rake created from an indent
     */
    public function storeRakeFromIndent(Request $request, Indent $indent): RedirectResponse
    {
        // $this->authorize('createRake', $indent);

        // Check if rake already exists for this indent
        if ($indent->rake) {
            return to_route('indents.show', $indent)
                ->with('error', 'A rake already exists for this indent.');
        }

        $validated = $request->validate([
            'rake_type' => ['nullable', 'string', 'max:50'],
            'wagon_count' => ['nullable', 'integer', 'min:0'],
            'free_time_minutes' => ['nullable', 'integer', 'min:0'],
            'rr_expected_date' => ['nullable', 'date'],
            'placement_time' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        // Create rake from indent with form data
        $rake = new Rake;
        $rake->indent_id = $indent->id;
        $rake->siding_id = $indent->siding_id; // Use indent's siding
        $rake->rake_number = 'RK-'.$indent->indent_number;
        $rake->rake_type = $validated['rake_type'] ?? 'standard';
        $rake->wagon_count = $validated['wagon_count'] ?? 0;
        $rake->loaded_weight_mt = 0;
        $rake->predicted_weight_mt = $indent->target_quantity_mt;
        $rake->state = 'pending';
        $rake->free_time_minutes = $validated['free_time_minutes'] ?? config('rrmcs.default_free_time_minutes', 180);
        $rake->rr_expected_date = $validated['rr_expected_date'] ?? $indent->required_by_date;
        $rake->placement_time = $validated['placement_time'] ? new DateTimeImmutable($validated['placement_time']) : null;
        $rake->created_by = $request->user()->id;
        $rake->updated_by = $request->user()->id;
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

        return to_route('rakes.show', $rake)
            ->with('success', 'Rake created from completed indent successfully.');
    }
}
