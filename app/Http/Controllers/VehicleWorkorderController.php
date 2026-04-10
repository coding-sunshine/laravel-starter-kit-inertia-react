<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\VehicleWorkorderExport;
use App\Http\Requests\IndexVehicleWorkorderRequest;
use App\Http\Requests\StoreVehicleWorkorderRequest;
use App\Http\Requests\UpdateVehicleWorkorderRequest;
use App\Models\Siding;
use App\Models\User;
use App\Models\VehicleWorkorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class VehicleWorkorderController extends Controller
{
    public function index(IndexVehicleWorkorderRequest $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $request->validated();

        $vehicleWorkorders = $this->vehicleWorkordersBaseQuery($user, $filters)
            ->paginate(15)
            ->withQueryString();

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $filterKeys = [
            'siding_id',
            'vehicle_no',
            'wo_no',
            'transport_name',
            'mobile',
            'model',
            'regd_date',
            'permit_validity_date',
            'tax_validity_date',
            'insurance_validity_date',
        ];

        return Inertia::render('VehicleWorkorders/Index', [
            'vehicleWorkorders' => $vehicleWorkorders,
            'sidings' => $sidings,
            'filters' => $request->only($filterKeys),
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ]);
    }

    public function export(IndexVehicleWorkorderRequest $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $request->validated();

        $rows = $this->vehicleWorkordersBaseQuery($user, $filters)->get();

        $filename = 'Vehicle_Workorders_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new VehicleWorkorderExport($rows), $filename);
    }

    public function create(): Response
    {
        $user = Auth::user();

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('VehicleWorkorders/Create', [
            'sidings' => $sidings,
        ]);
    }

    public function edit(VehicleWorkorder $vehicleWorkorder): Response|RedirectResponse
    {
        $user = Auth::user();
        if (! $user->canAccessSiding($vehicleWorkorder->siding_id)) {
            abort(403, 'You do not have access to this work order.');
        }

        $vehicleWorkorder->load('siding:id,name,code');

        return Inertia::render('VehicleWorkorders/Edit', [
            'vehicleWorkorder' => $vehicleWorkorder,
        ]);
    }

    public function store(StoreVehicleWorkorderRequest $request): RedirectResponse
    {
        VehicleWorkorder::query()->create($request->validated());

        return redirect()
            ->route('vehicle-workorders.index')
            ->with('success', 'Vehicle work order created successfully.');
    }

    public function update(UpdateVehicleWorkorderRequest $request, VehicleWorkorder $vehicleWorkorder): RedirectResponse
    {
        $vehicleWorkorder->update($request->validated());

        return redirect()
            ->route('vehicle-workorders.index')
            ->with('success', 'Vehicle work order updated successfully.');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function vehicleWorkordersBaseQuery(User $user, array $filters): Builder
    {
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $mobile = isset($filters['mobile']) && is_string($filters['mobile']) ? mb_trim($filters['mobile']) : '';
        $model = isset($filters['model']) && is_string($filters['model']) ? mb_trim($filters['model']) : '';

        return VehicleWorkorder::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->when(
                ! empty($filters['siding_id'] ?? null),
                fn (Builder $q) => $q->where('siding_id', (int) $filters['siding_id']),
            )
            ->when(
                ! empty($filters['vehicle_no'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'vehicle_no ILIKE ?',
                    ['%'.addcslashes((string) $filters['vehicle_no'], '%_\\').'%'],
                ),
            )
            ->when(
                ! empty($filters['wo_no'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'wo_no ILIKE ?',
                    ['%'.addcslashes((string) $filters['wo_no'], '%_\\').'%'],
                ),
            )
            ->when(
                ! empty($filters['transport_name'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'transport_name ILIKE ?',
                    ['%'.addcslashes((string) $filters['transport_name'], '%_\\').'%'],
                ),
            )
            ->when($mobile !== '', function (Builder $q) use ($mobile): void {
                $pattern = '%'.addcslashes($mobile, '%_\\').'%';
                $q->where(function (Builder $inner) use ($pattern): void {
                    $inner->whereRaw('mobile_no_1 ILIKE ?', [$pattern])
                        ->orWhereRaw('mobile_no_2 ILIKE ?', [$pattern]);
                });
            })
            ->when(
                $model !== '',
                fn (Builder $q) => $q->whereRaw('model ILIKE ?', ['%'.addcslashes($model, '%_\\').'%']),
            )
            ->when(
                ! empty($filters['regd_date'] ?? null),
                fn (Builder $q) => $q->whereDate('regd_date', $filters['regd_date']),
            )
            ->when(
                ! empty($filters['permit_validity_date'] ?? null),
                fn (Builder $q) => $q->whereDate('permit_validity_date', $filters['permit_validity_date']),
            )
            ->when(
                ! empty($filters['tax_validity_date'] ?? null),
                fn (Builder $q) => $q->whereDate('tax_validity_date', $filters['tax_validity_date']),
            )
            ->when(
                ! empty($filters['insurance_validity_date'] ?? null),
                fn (Builder $q) => $q->whereDate('insurance_validity_date', $filters['insurance_validity_date']),
            )
            ->orderBy('work_order_date', 'desc')
            ->orderBy('created_at', 'desc');
    }
}
