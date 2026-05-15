<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\TransportWorkOrderRegistrationExport;
use App\Exports\VehicleWorkorderExport;
use App\Http\Requests\IndexVehicleWorkorderRequest;
use App\Http\Requests\StoreVehicleWorkorderRequest;
use App\Http\Requests\UpdateVehicleWorkorderRequest;
use App\Models\Siding;
use App\Models\TransportWorkOrderRegistration;
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

        $view = $filters['view'] ?? 'vehicles';

        $vehicleWorkorders = null;
        $transportWorkOrderRegistrations = null;

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if ($view === 'transporters') {
            $transportWorkOrderRegistrations = $this->transportWorkOrderRegistrationsBaseQuery($sidingIds, $filters)
                ->paginate(15)
                ->withQueryString();
        } else {
            $vehicleWorkorders = $this->vehicleWorkordersBaseQuery($user, $filters)
                ->paginate(15)
                ->withQueryString();
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        if ($view === 'transporters') {
            /** @var array<int, string> */
            $transportNames = TransportWorkOrderRegistration::query()
                ->where(function (Builder $q) use ($sidingIds): void {
                    $q->whereNull('siding_id')
                        ->orWhereIn('siding_id', $sidingIds);
                })
                ->whereNotNull('transporter_name')
                ->where('transporter_name', '!=', '')
                ->distinct()
                ->orderBy('transporter_name')
                ->pluck('transporter_name')
                ->values()
                ->all();
            /** @var array<int, string> */
            $proprietorNames = [];
        } else {
            // Dropdown options: all distinct names across every siding the user can access.
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
        }

        $filterKeys = [
            'view',
            'page',
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
            'transportWorkOrderRegistrations' => $transportWorkOrderRegistrations,
            'transportRegistrationPermissions' => [
                'canCreate' => $user->hasPermissionTo('sections.transport.create'),
                'canUpdate' => $user->hasPermissionTo('sections.transport.update'),
                'canDelete' => $user->hasPermissionTo('sections.transport.update'),
            ],
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

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $rows = $this->transportWorkOrderRegistrationsBaseQuery($sidingIds, $filters)->get();

        $filename = 'Transport_Work_Order_Registrations_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new TransportWorkOrderRegistrationExport($rows), $filename);
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
     * @param  array<int, int|string>  $sidingIds
     * @param  array<string, mixed>  $filters
     */
    private function transportWorkOrderRegistrationsBaseQuery(array $sidingIds, array $filters): Builder
    {
        return TransportWorkOrderRegistration::query()
            ->where(function (Builder $q) use ($sidingIds): void {
                $q->whereNull('siding_id')
                    ->orWhereIn('siding_id', $sidingIds);
            })
            ->with('siding:id,name,code')
            ->when(
                ! empty($filters['siding_id'] ?? null),
                fn (Builder $q) => $q->where('siding_id', (int) $filters['siding_id']),
            )
            ->when(
                ! empty($filters['transport_name'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['transport_name']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(transporter_name, \'\'))) LIKE LOWER(?)',
                        ['%'.$needle.'%'],
                    );
                },
            )
            ->when(
                ! empty($filters['wo_no'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['wo_no']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(work_order_no_1, \'\'))) LIKE LOWER(?)',
                        ['%'.$needle.'%'],
                    );
                },
            )
            ->when(
                ! empty($filters['wo_no_2'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['wo_no_2']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(work_order_no_2, \'\'))) LIKE LOWER(?)',
                        ['%'.$needle.'%'],
                    );
                },
            )
            ->when(
                ! empty($filters['work_order_date'] ?? null),
                fn (Builder $q) => $q->whereDate('work_order_date', $filters['work_order_date']),
            )
            ->when(
                ! empty($filters['address'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['address']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(address, \'\'))) LIKE LOWER(?)',
                        ['%'.$needle.'%'],
                    );
                },
            )
            ->when(
                ! empty($filters['mobile_no_1'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['mobile_no_1']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(mobile_1, \'\'))) = LOWER(?)',
                        [$needle],
                    );
                },
            )
            ->when(
                ! empty($filters['mobile_no_2'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['mobile_no_2']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(mobile_2, \'\'))) = LOWER(?)',
                        [$needle],
                    );
                },
            )
            ->when(
                ! empty($filters['pan_no'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['pan_no']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(pan_card, \'\'))) = LOWER(?)',
                        [$needle],
                    );
                },
            )
            ->when(
                ! empty($filters['gst_no'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['gst_no']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(gst_no, \'\'))) = LOWER(?)',
                        [$needle],
                    );
                },
            )
            ->orderByDesc('work_order_date')
            ->orderByDesc('created_at');
    }
}
