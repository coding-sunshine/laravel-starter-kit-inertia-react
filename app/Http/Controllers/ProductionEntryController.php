<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionEntryRequest;
use App\Http\Requests\UpdateProductionEntryRequest;
use App\Models\ProductionEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProductionEntryController extends Controller
{
    private const ROUTE_NAMES = [
        'production.coal.index' => 'coal',
        'production.coal.store' => 'coal',
        'production.coal.edit' => 'coal',
        'production.coal.update' => 'coal',
        'production.coal.destroy' => 'coal',
        'production.ob.index' => 'ob',
        'production.ob.store' => 'ob',
        'production.ob.edit' => 'ob',
        'production.ob.update' => 'ob',
        'production.ob.destroy' => 'ob',
    ];

    public function index(Request $request): InertiaResponse
    {
        $type = $this->typeFromRoute($request);
        $entries = ProductionEntry::query()
            ->where('type', $type)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('production/index', [
            'entries' => $entries->values()->all(),
            'type' => $type,
        ]);
    }

    public function store(StoreProductionEntryRequest $request): RedirectResponse
    {
        $type = $this->typeFromRoute($request);
        $data = $request->validated();
        $data['type'] = $type;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        ProductionEntry::create($data);

        $routeName = $type === ProductionEntry::TYPE_COAL ? 'production.coal.index' : 'production.ob.index';

        return redirect()->route($routeName);
    }

    public function edit(Request $request, ProductionEntry $production_entry): InertiaResponse|RedirectResponse
    {
        $type = $this->typeFromRoute($request);
        if ($production_entry->type !== $type) {
            return redirect()->route($type === ProductionEntry::TYPE_COAL ? 'production.coal.index' : 'production.ob.index')
                ->with('error', 'Entry does not belong to this production type.');
        }

        return Inertia::render('production/edit', [
            'entry' => $production_entry,
            'type' => $type,
        ]);
    }

    public function update(UpdateProductionEntryRequest $request, ProductionEntry $production_entry): RedirectResponse
    {
        $type = $this->typeFromRoute($request);
        if ($production_entry->type !== $type) {
            return redirect()->route($type === ProductionEntry::TYPE_COAL ? 'production.coal.index' : 'production.ob.index')
                ->with('error', 'Entry does not belong to this production type.');
        }

        $data = $request->validated();
        $data['updated_by'] = auth()->id();
        $production_entry->update($data);

        $routeName = $type === ProductionEntry::TYPE_COAL ? 'production.coal.index' : 'production.ob.index';

        return redirect()->route($routeName);
    }

    public function destroy(Request $request, ProductionEntry $production_entry): RedirectResponse
    {
        $type = $this->typeFromRoute($request);
        if ($production_entry->type !== $type) {
            return redirect()->route($type === ProductionEntry::TYPE_COAL ? 'production.coal.index' : 'production.ob.index')
                ->with('error', 'Entry does not belong to this production type.');
        }

        $production_entry->delete();

        $routeName = $type === ProductionEntry::TYPE_COAL ? 'production.coal.index' : 'production.ob.index';

        return redirect()->route($routeName);
    }

    private function typeFromRoute(Request $request): string
    {
        $name = $request->route()?->getName();
        if ($name !== null && isset(self::ROUTE_NAMES[$name])) {
            return self::ROUTE_NAMES[$name];
        }

        throw ValidationException::withMessages(['type' => ['Invalid production type.']])
            ->status(Response::HTTP_NOT_FOUND);
    }
}
