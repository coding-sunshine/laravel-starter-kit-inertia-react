<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTrailerRequest;
use App\Http\Requests\Fleet\UpdateTrailerRequest;
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

    public function store(StoreTrailerRequest $request): RedirectResponse
    {
        $this->authorize('create', Trailer::class);
        Trailer::create($request->validated());
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

    public function update(UpdateTrailerRequest $request, Trailer $trailer): RedirectResponse
    {
        $this->authorize('update', $trailer);
        $trailer->update($request->validated());
        return to_route('fleet.trailers.show', $trailer)->with('flash', ['status' => 'success', 'message' => 'Trailer updated.']);
    }

    public function destroy(Trailer $trailer): RedirectResponse
    {
        $this->authorize('delete', $trailer);
        $trailer->delete();
        return to_route('fleet.trailers.index')->with('flash', ['status' => 'success', 'message' => 'Trailer deleted.']);
    }
}
