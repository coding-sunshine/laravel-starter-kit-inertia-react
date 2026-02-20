<?php

declare(strict_types=1);

namespace App\Http\Controllers\Indents;

use App\Http\Controllers\Controller;
use App\Models\Indent;
use App\Models\Siding;
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
        $this->authorize('create', Indent::class);

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
        $this->authorize('create', Indent::class);

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

        return redirect()
            ->route('indents.show', $indent)
            ->with('success', 'Indent created.');
    }

    public function show(Request $request, Indent $indent): Response
    {
        $this->authorize('view', $indent);

        $indent->load('siding:id,name,code');

        return Inertia::render('indents/show', [
            'indent' => $indent,
        ]);
    }

    public function edit(Request $request, Indent $indent): Response
    {
        $this->authorize('update', $indent);

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
        $this->authorize('update', $indent);

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

        return redirect()
            ->route('indents.show', $indent)
            ->with('success', 'Indent updated.');
    }
}
