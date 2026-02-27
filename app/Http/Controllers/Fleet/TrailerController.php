<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Trailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TrailerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Trailer::class);
        $trailers = Trailer::query()
            ->with('homeLocation')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('fleet_number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Trailers/Index', [
            'trailers' => $trailers,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Trailer::class);
        return Inertia::render('Fleet/Trailers/Create', [
            'types' => \App\Enums\Fleet\TrailerType::cases(),
            'statuses' => \App\Enums\Fleet\TrailerStatus::cases(),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Trailer::class);
        $validated = $request->validate([
            'registration' => ['nullable', 'string', 'max:50'],
            'fleet_number' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:flatbed,box,tank,refrigerated,lowloader,other'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'home_location_id' => ['nullable', 'exists:locations,id'],
            'status' => ['required', 'string', 'in:active,maintenance,vor,disposed'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
        ]);
        Trailer::create($validated);
        return to_route('fleet.trailers.index')->with('flash', ['status' => 'success', 'message' => 'Trailer created.']);
    }

    public function show(Trailer $trailer): Response
    {
        $this->authorize('view', $trailer);
        $trailer->load('homeLocation');
        return Inertia::render('Fleet/Trailers/Show', ['trailer' => $trailer]);
    }

    public function edit(Trailer $trailer): Response
    {
        $this->authorize('update', $trailer);
        return Inertia::render('Fleet/Trailers/Edit', [
            'trailer' => $trailer,
            'types' => \App\Enums\Fleet\TrailerType::cases(),
            'statuses' => \App\Enums\Fleet\TrailerStatus::cases(),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Trailer $trailer): RedirectResponse
    {
        $this->authorize('update', $trailer);
        $validated = $request->validate([
            'registration' => ['nullable', 'string', 'max:50'],
            'fleet_number' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:flatbed,box,tank,refrigerated,lowloader,other'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'home_location_id' => ['nullable', 'exists:locations,id'],
            'status' => ['required', 'string', 'in:active,maintenance,vor,disposed'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
        ]);
        $trailer->update($validated);
        return to_route('fleet.trailers.show', $trailer)->with('flash', ['status' => 'success', 'message' => 'Trailer updated.']);
    }

    public function destroy(Trailer $trailer): RedirectResponse
    {
        $this->authorize('delete', $trailer);
        $trailer->delete();
        return to_route('fleet.trailers.index')->with('flash', ['status' => 'success', 'message' => 'Trailer deleted.']);
    }
}
