<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\VehicleWorkorderExport;
use App\Exports\VehicleWorkorderTransporterExport;
use App\Http\Requests\IndexVehicleWorkorderRequest;
use App\Http\Requests\StoreVehicleWorkorderRequest;
use App\Http\Requests\UpdateVehicleWorkorderRequest;
use App\Models\Siding;
use App\Models\User;
use App\Models\VehicleWorkorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        $view = $filters['view'] ?? 'vehicles';

        $vehicleWorkorders = null;
        $transporterWorkorders = null;

        if ($view === 'transporters') {
            $transporterWorkorders = $this->transporterWorkordersAggregatedQuery($user, $filters)
                ->paginate(15)
                ->withQueryString();
        } else {
            $vehicleWorkorders = $this->vehicleWorkordersBaseQuery($user, $filters)
                ->paginate(15)
                ->withQueryString();
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Dropdown options: all distinct names across every siding the user can access.
        // Do not scope by the current siding filter — otherwise names from other sidings
        // disappear from the list while the table filter still applies siding separately.
        $transportNames = VehicleWorkorder::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('transport_name')
            ->where('transport_name', '!=', '')
            ->distinct()
            ->orderBy('transport_name')
            ->pluck('transport_name')
            ->values()
            ->all();

        /** @var array<int, string> */
        $proprietorNames = VehicleWorkorder::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('proprietor_name')
            ->where('proprietor_name', '!=', '')
            ->distinct()
            ->orderBy('proprietor_name')
            ->pluck('proprietor_name')
            ->values()
            ->all();

        $filterKeys = [
            'view',
            'siding_id',
            'vehicle_no',
            'wo_no',
            'wo_no_2',
            'transport_name',
            'mobile',
            'mobile_no_1',
            'mobile_no_2',
            'model',
            'work_order_date',
            'issued_date',
            'proprietor_name',
            'address',
            'owner_type',
            'pan_no',
            'gst_no',
            'min_vehicles',
            'max_vehicles',
            'regd_date',
            'permit_validity_date',
            'tax_validity_date',
            'insurance_validity_date',
        ];

        return Inertia::render('VehicleWorkorders/Index', [
            'view' => $view,
            'vehicleWorkorders' => $vehicleWorkorders,
            'transporterWorkorders' => $transporterWorkorders,
            'sidings' => $sidings,
            'transportNames' => $transportNames,
            'proprietorNames' => $proprietorNames,
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

    public function exportTransporters(IndexVehicleWorkorderRequest $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $request->validated();

        $rows = $this->transporterWorkordersAggregatedQuery($user, $filters)->get();

        $filename = 'Transporter_Workorders_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new VehicleWorkorderTransporterExport($rows), $filename);
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
        return $this->vehicleWorkordersFilteredQuery($user, $filters)
            ->with('siding:id,name,code')
            ->orderBy('work_order_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function vehicleWorkordersFilteredQuery(User $user, array $filters): Builder
    {
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $mobile = isset($filters['mobile']) && is_string($filters['mobile']) ? mb_trim($filters['mobile']) : '';
        $mobileNo1 = isset($filters['mobile_no_1']) && is_string($filters['mobile_no_1']) ? mb_trim($filters['mobile_no_1']) : '';
        $mobileNo2 = isset($filters['mobile_no_2']) && is_string($filters['mobile_no_2']) ? mb_trim($filters['mobile_no_2']) : '';
        $model = isset($filters['model']) && is_string($filters['model']) ? mb_trim($filters['model']) : '';

        return VehicleWorkorder::query()
            ->whereIn('siding_id', $sidingIds)
            ->when(
                ! empty($filters['siding_id'] ?? null),
                fn (Builder $q) => $q->where('siding_id', (int) $filters['siding_id']),
            )
            ->when(
                ! empty($filters['vehicle_no'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(vehicle_no, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['vehicle_no'])],
                ),
            )
            ->when(
                ! empty($filters['wo_no'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(wo_no, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['wo_no'])],
                ),
            )
            ->when(
                ! empty($filters['wo_no_2'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(wo_no_2, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['wo_no_2'])],
                ),
            )
            ->when(
                ! empty($filters['transport_name'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(transport_name, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['transport_name'])],
                ),
            )
            ->when($mobile !== '', function (Builder $q) use ($mobile): void {
                $q->where(function (Builder $inner) use ($mobile): void {
                    $inner->whereRaw(
                        'LOWER(TRIM(COALESCE(mobile_no_1, \'\'))) = LOWER(?)',
                        [$mobile],
                    )->orWhereRaw(
                        'LOWER(TRIM(COALESCE(mobile_no_2, \'\'))) = LOWER(?)',
                        [$mobile],
                    );
                });
            })
            ->when(
                $mobileNo1 !== '',
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(mobile_no_1, \'\'))) = LOWER(?)',
                    [$mobileNo1],
                ),
            )
            ->when(
                $mobileNo2 !== '',
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(mobile_no_2, \'\'))) = LOWER(?)',
                    [$mobileNo2],
                ),
            )
            ->when(
                $model !== '',
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(model, \'\'))) = LOWER(?)',
                    [$model],
                ),
            )
            ->when(
                ! empty($filters['work_order_date'] ?? null),
                fn (Builder $q) => $q->whereDate('work_order_date', $filters['work_order_date']),
            )
            ->when(
                ! empty($filters['issued_date'] ?? null),
                fn (Builder $q) => $q->whereDate('issued_date', $filters['issued_date']),
            )
            ->when(
                ! empty($filters['proprietor_name'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(proprietor_name, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['proprietor_name'])],
                ),
            )
            ->when(
                ! empty($filters['address'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(address, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['address'])],
                ),
            )
            ->when(
                ! empty($filters['owner_type'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(owner_type, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['owner_type'])],
                ),
            )
            ->when(
                ! empty($filters['pan_no'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(pan_no, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['pan_no'])],
                ),
            )
            ->when(
                ! empty($filters['gst_no'] ?? null),
                fn (Builder $q) => $q->whereRaw(
                    'LOWER(TRIM(COALESCE(gst_no, \'\'))) = LOWER(?)',
                    [mb_trim((string) $filters['gst_no'])],
                ),
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
            );
    }

    /**
     * One row per transporter work order (grouped), with vehicle count.
     *
     * @param  array<string, mixed>  $filters
     */
    private function transporterWorkordersAggregatedQuery(User $user, array $filters): Builder
    {
        $vw = 'vehicle_workorders';

        return $this->vehicleWorkordersFilteredQuery($user, $filters)
            ->join('sidings', 'sidings.id', '=', "{$vw}.siding_id")
            ->select([
                "{$vw}.siding_id",
                DB::raw('MAX(sidings.name) as siding_name'),
                DB::raw("MAX({$vw}.transport_name) as transport_name"),
                DB::raw("MAX({$vw}.wo_no) as wo_no"),
                DB::raw("MAX({$vw}.wo_no_2) as wo_no_2"),
                DB::raw("MAX({$vw}.work_order_date) as work_order_date"),
                DB::raw("MAX({$vw}.issued_date) as issued_date"),
                DB::raw("MAX({$vw}.proprietor_name) as proprietor_name"),
                DB::raw("MAX({$vw}.address) as address"),
                DB::raw("MAX({$vw}.mobile_no_1) as mobile_no_1"),
                DB::raw("MAX({$vw}.mobile_no_2) as mobile_no_2"),
                DB::raw("MAX({$vw}.owner_type) as owner_type"),
                DB::raw("MAX({$vw}.pan_no) as pan_no"),
                DB::raw("MAX({$vw}.gst_no) as gst_no"),
                DB::raw('COUNT(*) as vehicle_count'),
            ])
            ->groupBy([
                "{$vw}.siding_id",
                DB::raw("COALESCE({$vw}.transport_name, '')"),
                DB::raw("COALESCE({$vw}.wo_no, '')"),
                DB::raw("COALESCE({$vw}.wo_no_2, '')"),
                "{$vw}.work_order_date",
                "{$vw}.issued_date",
            ])
            ->when(
                isset($filters['min_vehicles']) && $filters['min_vehicles'] !== null && $filters['min_vehicles'] !== '',
                fn (Builder $q) => $q->havingRaw('COUNT(*) >= ?', [(int) $filters['min_vehicles']]),
            )
            ->when(
                isset($filters['max_vehicles']) && $filters['max_vehicles'] !== null && $filters['max_vehicles'] !== '',
                fn (Builder $q) => $q->havingRaw('COUNT(*) <= ?', [(int) $filters['max_vehicles']]),
            )
            ->orderByRaw("MAX({$vw}.work_order_date) DESC NULLS LAST")
            ->orderByRaw("MAX({$vw}.created_at) DESC");
    }
}
