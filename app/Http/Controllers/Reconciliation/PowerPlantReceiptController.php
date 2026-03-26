<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\PowerPlant;
use App\Models\PowerPlantReceipt;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PowerPlantReceiptController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $query = PowerPlantReceipt::query()
            ->with('rake:id,rake_number', 'powerPlant:id,name,code')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->latest('receipt_date');

        $receipts = $query->paginate(15)->withQueryString();
        $rakes = Rake::query()->whereIn('siding_id', $sidingIds)->orderBy('rake_number')->get(['id', 'rake_number']);
        $powerPlants = PowerPlant::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('reconciliation/power-plant-receipts/index', [
            'receipts' => $receipts,
            'rakes' => $rakes,
            'powerPlants' => $powerPlants,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();
        $rakes = Rake::query()->whereIn('siding_id', $sidingIds)->orderBy('rake_number')->get(['id', 'rake_number']);
        $powerPlants = PowerPlant::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('reconciliation/power-plant-receipts/create', [
            'rakes' => $rakes,
            'powerPlants' => $powerPlants,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rake_id' => ['required', 'integer', 'exists:rakes,id'],
            'power_plant_id' => ['required', 'integer', 'exists:power_plants,id'],
            'receipt_date' => ['required', 'date'],
            'weight_mt' => ['required', 'numeric', 'min:0'],
            'rr_reference' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'in:pending,reached,verified,discrepancy'],
        ]);
        $rake = Rake::query()->findOrFail($validated['rake_id']);
        // $this->authorize('view', $rake);

        PowerPlantReceipt::query()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return to_route('reconciliation.power-plant-receipts.index')->with('success', 'Power plant receipt saved.');
    }
}
