<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreRouteRequest;
use App\Http\Requests\Fleet\UpdateRouteRequest;
use App\Models\Fleet\Route;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RouteController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Route::class);
        $routes = Route::query()
            ->with(['startLocation', 'endLocation'])
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->input('route_type'), fn ($q, $type) => $q->where('route_type', $type))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Routes/Index', [
            'routes' => $routes,
            'filters' => $request->only(['is_active', 'route_type']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Route::class);
        return Inertia::render('Fleet/Routes/Create', [
            'routeTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\RouteType::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreRouteRequest $request): RedirectResponse
    {
        $this->authorize('create', Route::class);
        Route::create($request->validated());
        return to_route('fleet.routes.index')->with('flash', ['status' => 'success', 'message' => 'Route created.']);
    }

    public function show(Route $route): Response
    {
        $this->authorize('view', $route);
        $route->load(['startLocation', 'endLocation', 'stops' => fn ($q) => $q->with('location')->orderBy('sort_order')]);
        return Inertia::render('Fleet/Routes/Show', [
            'route' => $route,
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Route $route): Response
    {
        $this->authorize('update', $route);
        return Inertia::render('Fleet/Routes/Edit', [
            'route' => $route,
            'routeTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\RouteType::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateRouteRequest $request, Route $route): RedirectResponse
    {
        $this->authorize('update', $route);
        $route->update($request->validated());
        return to_route('fleet.routes.show', $route)->with('flash', ['status' => 'success', 'message' => 'Route updated.']);
    }

    public function destroy(Route $route): RedirectResponse
    {
        $this->authorize('delete', $route);
        $route->delete();
        return to_route('fleet.routes.index')->with('flash', ['status' => 'success', 'message' => 'Route deleted.']);
    }
}
