<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\OperatorLicence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OperatorLicenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OperatorLicence::class);
        $operatorLicences = OperatorLicence::query()
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('license_number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/OperatorLicences/Index', [
            'operatorLicences' => $operatorLicences,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', OperatorLicence::class);
        return Inertia::render('Fleet/OperatorLicences/Create', [
            'statuses' => \App\Enums\Fleet\OperatorLicenceStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', OperatorLicence::class);
        $validated = $request->validate([
            'license_number' => ['required', 'string', 'max:50'],
            'license_type' => ['required', 'string', 'in:standard_national,standard_international,restricted'],
            'traffic_commissioner_area' => ['required', 'string', 'in:north_eastern,north_western,west_midlands,eastern,western,southern,scottish'],
            'issue_date' => ['required', 'date'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date'],
            'authorized_vehicles' => ['required', 'integer', 'min:0'],
            'authorized_trailers' => ['nullable', 'integer', 'min:0'],
            'operating_centres' => ['required', 'array'],
            'operating_centres.*.name' => ['required', 'string'],
            'operating_centres.*.address' => ['required', 'string'],
            'status' => ['required', 'string', 'in:active,suspended,revoked,surrendered,applied,pending_review'],
        ]);
        OperatorLicence::create($validated);
        return to_route('fleet.operator-licences.index')->with('flash', ['status' => 'success', 'message' => 'Operator licence created.']);
    }

    public function show(OperatorLicence $operatorLicence): Response
    {
        $this->authorize('view', $operatorLicence);
        return Inertia::render('Fleet/OperatorLicences/Show', ['operatorLicence' => $operatorLicence]);
    }

    public function edit(OperatorLicence $operatorLicence): Response
    {
        $this->authorize('update', $operatorLicence);
        return Inertia::render('Fleet/OperatorLicences/Edit', [
            'operatorLicence' => $operatorLicence,
            'statuses' => \App\Enums\Fleet\OperatorLicenceStatus::cases(),
        ]);
    }

    public function update(Request $request, OperatorLicence $operatorLicence): RedirectResponse
    {
        $this->authorize('update', $operatorLicence);
        $validated = $request->validate([
            'license_number' => ['required', 'string', 'max:50'],
            'license_type' => ['required', 'string', 'in:standard_national,standard_international,restricted'],
            'traffic_commissioner_area' => ['required', 'string', 'in:north_eastern,north_western,west_midlands,eastern,western,southern,scottish'],
            'issue_date' => ['required', 'date'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date'],
            'authorized_vehicles' => ['required', 'integer', 'min:0'],
            'authorized_trailers' => ['nullable', 'integer', 'min:0'],
            'operating_centres' => ['required', 'array'],
            'status' => ['required', 'string', 'in:active,suspended,revoked,surrendered,applied,pending_review'],
        ]);
        $operatorLicence->update($validated);
        return to_route('fleet.operator-licences.show', $operatorLicence)->with('flash', ['status' => 'success', 'message' => 'Operator licence updated.']);
    }

    public function destroy(OperatorLicence $operatorLicence): RedirectResponse
    {
        $this->authorize('delete', $operatorLicence);
        $operatorLicence->delete();
        return to_route('fleet.operator-licences.index')->with('flash', ['status' => 'success', 'message' => 'Operator licence deleted.']);
    }
}
