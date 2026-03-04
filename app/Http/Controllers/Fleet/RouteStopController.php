<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreRouteStopRequest;
use App\Http\Requests\Fleet\UpdateRouteStopRequest;
use App\Models\Fleet\Route;
use App\Models\Fleet\RouteStop;
use Illuminate\Http\RedirectResponse;

final class RouteStopController extends Controller
{
    public function store(StoreRouteStopRequest $request, Route $route): RedirectResponse
    {
        $this->authorize('create', RouteStop::class);
        $maxOrder = $route->stops()->max('sort_order') ?? 0;
        $route->stops()->create(array_merge($request->validated(), [
            'sort_order' => $request->input('sort_order', $maxOrder + 1),
        ]));

        return to_route('fleet.routes.show', $route)->with('flash', ['status' => 'success', 'message' => 'Stop added.']);
    }

    public function update(UpdateRouteStopRequest $request, Route $route, RouteStop $routeStop): RedirectResponse
    {
        $this->authorize('update', $routeStop);
        $routeStop->update($request->validated());

        return to_route('fleet.routes.show', $route)->with('flash', ['status' => 'success', 'message' => 'Stop updated.']);
    }

    public function destroy(Route $route, RouteStop $routeStop): RedirectResponse
    {
        $this->authorize('delete', $routeStop);
        $routeStop->delete();

        return to_route('fleet.routes.show', $route)->with('flash', ['status' => 'success', 'message' => 'Stop removed.']);
    }
}
