<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MapTransportRegistrationToVehicleWorkorderDefaults;
use App\Exports\TransportWorkOrderRegistrationExport;
use App\Exports\VehicleWorkorderExport;
use App\Http\Requests\DestroyVehicleWorkorderMediaRequest;
use App\Http\Requests\IndexVehicleWorkorderRequest;
use App\Http\Requests\StoreVehicleWorkorderRequest;
use App\Http\Requests\UpdateVehicleWorkorderRequest;
use App\Models\Siding;
use App\Models\TransportWorkOrderRegistration;
use App\Models\User;
use App\Models\VehicleWorkorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
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
                ->paginate(50)
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

        /** @var array<int, string> */
        $transportNames = $view === 'transporters'
            ? TransportWorkOrderRegistration::query()
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
                ->all()
            : VehicleWorkorder::query()
                ->whereIn('siding_id', $sidingIds)
                ->whereNotNull('transport_name')
                ->where('transport_name', '!=', '')
                ->distinct()
                ->orderBy('transport_name')
                ->pluck('transport_name')
                ->values()
                ->all();

        /** @var list<string> */
        $filterKeys = [
            'view',
            'page',
            'siding_id',
            'transport_name',
            'vehicle_no',
            'regd_date',
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

    public function searchTransportRegistrationsForVehicleWorkorder(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $needle = mb_trim((string) $request->query('q', ''));
        $like = '%'.$this->escapeSqlLikePattern($needle).'%';

        /** @var Builder $query */
        $query = TransportWorkOrderRegistration::query()
            ->where('is_active', true)
            ->where(function (Builder $q) use ($sidingIds): void {
                $q->whereNull('siding_id')
                    ->orWhereIn('siding_id', $sidingIds);
            })
            ->when(
                $needle !== '',
                fn (Builder $q) => $q->where(function (Builder $inner) use ($like): void {
                    $inner->whereRaw(
                        'LOWER(COALESCE(transporter_name, \'\')) LIKE LOWER(?)',
                        [$like],
                    )->orWhereRaw(
                        'LOWER(COALESCE(work_order_no_1, \'\')) LIKE LOWER(?)',
                        [$like],
                    )->orWhereRaw(
                        'LOWER(COALESCE(work_order_no_2, \'\')) LIKE LOWER(?)',
                        [$like],
                    );
                }),
            )
            ->orderByRaw('COALESCE(transporter_name, \'\') ASC')
            ->orderByRaw('COALESCE(work_order_no_1, \'\') ASC')
            ->limit(50);

        $mapper = app(MapTransportRegistrationToVehicleWorkorderDefaults::class);

        $data = $query->get()->map(function (TransportWorkOrderRegistration $r) use ($mapper): array {
            return [
                'id' => $r->id,
                'label' => $mapper->registrationPickerLabel($r),
                'defaults' => $mapper->handle($r),
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    public function edit(VehicleWorkorder $vehicleWorkorder): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->canAccessSiding($vehicleWorkorder->siding_id)) {
            abort(403, 'You do not have access to this work order.');
        }

        $vehicleWorkorder->load('siding:id,name,code');

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $vehicleWorkorder->loadMissing('media');

        return Inertia::render('VehicleWorkorders/Edit', [
            'vehicleWorkorder' => $vehicleWorkorder,
            'matchedTransportRegistration' => $this->matchedTransportRegistrationForVehicleWorkorder($vehicleWorkorder, $user),
            'sidings' => $sidings,
            'vehicleDocumentMedia' => $this->vehicleWorkorderDocumentsPayload($vehicleWorkorder),
        ]);
    }

    public function store(StoreVehicleWorkorderRequest $request): RedirectResponse
    {
        $vehicleWorkorder = VehicleWorkorder::query()->create(
            $this->vehicleWorkorderRowFromRequest($request),
        );
        $this->persistVehicleWorkorderDocuments($vehicleWorkorder, $request);

        return redirect()
            ->route('vehicle-workorders.index')
            ->with('success', 'Vehicle work order created successfully.');
    }

    public function update(UpdateVehicleWorkorderRequest $request, VehicleWorkorder $vehicleWorkorder): RedirectResponse
    {
        $vehicleWorkorder->update($this->vehicleWorkorderRowFromRequest($request));
        $this->persistVehicleWorkorderDocuments($vehicleWorkorder, $request);

        return redirect()
            ->route('vehicle-workorders.index')
            ->with('success', 'Vehicle work order updated successfully.');
    }

    public function destroyMedia(
        DestroyVehicleWorkorderMediaRequest $request,
        VehicleWorkorder $vehicleWorkorder,
        Media $media,
    ): RedirectResponse {
        $allowedCollections = ['vehicle_rc', 'vehicle_insurance', 'vehicle_other_documents'];

        /** @var Media|null $owned */
        $owned = $vehicleWorkorder->media()
            ->whereKey($media->getKey())
            ->whereIn('collection_name', $allowedCollections)
            ->first();

        abort_if($owned === null, 404);

        $owned->delete();

        return redirect()
            ->route('vehicle-workorders.edit', ['vehicle_workorder' => $vehicleWorkorder])
            ->with('success', 'Attached file removed.');
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
                ! empty($filters['transport_name'] ?? null),
                function (Builder $q) use ($filters): void {
                    $needle = mb_trim((string) $filters['transport_name']);
                    $q->whereRaw(
                        'LOWER(TRIM(COALESCE(transport_name, \'\'))) LIKE LOWER(?)',
                        ['%'.$needle.'%'],
                    );
                },
            )
            ->when(
                ! empty($filters['regd_date'] ?? null),
                fn (Builder $q) => $q->whereDate('regd_date', $filters['regd_date']),
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
            ->orderByDesc('work_order_date')
            ->orderByDesc('created_at');
    }

    /**
     * Escapes `\`, `%`, `_` inside a substring used inside SQL LIKE `... LIKE ?`.
     */
    private function escapeSqlLikePattern(string $literal): string
    {
        $literal = str_replace('\\', '\\\\', $literal);

        return str_replace(['%', '_'], ['\\%', '\\_'], $literal);
    }

    /**
     * @return array<string, array<int, array{id: int, name: string, file_name: string, url: string}>>
     */
    private function vehicleWorkorderDocumentsPayload(VehicleWorkorder $workorder): array
    {
        $map = static function (Media $media): array {
            return [
                'id' => (int) $media->id,
                'name' => (string) $media->name,
                'file_name' => $media->file_name,
                'url' => $media->getUrl(),
            ];
        };

        return [
            'vehicle_rc' => $workorder->getMedia('vehicle_rc')->map($map)->values()->all(),
            'vehicle_insurance' => $workorder->getMedia('vehicle_insurance')->map($map)->values()->all(),
            'vehicle_other_documents' => $workorder->getMedia('vehicle_other_documents')->map($map)->values()->all(),
        ];
    }

    /**
     * Map validated request input to {@see VehicleWorkorder} columns (upload fields handled separately).
     *
     * @return array<string, mixed>
     */
    private function vehicleWorkorderRowFromRequest(StoreVehicleWorkorderRequest|UpdateVehicleWorkorderRequest $request): array
    {
        return [
            'siding_id' => $request->integer('siding_id'),
            'vehicle_no' => $request->vehicle_no,
            'rcd_pin_no' => $request->rcd_pin_no,
            'transport_name' => $request->transport_name,
            'wo_no' => $request->wo_no,
            'wo_no_2' => $request->wo_no_2,
            'work_order_date' => $request->work_order_date,
            'issued_date' => $request->issued_date,
            'proprietor_name' => $request->proprietor_name,
            'represented_by' => $request->represented_by,
            'place' => $request->place,
            'address' => $request->address,
            'tyres' => $request->integer('tyres'),
            'tare_weight' => $request->tare_weight,
            'mobile_no_1' => $request->mobile_no_1,
            'mobile_no_2' => $request->mobile_no_2,
            'owner_type' => $request->owner_type,
            'regd_date' => $request->regd_date,
            'permit_validity_date' => $request->permit_validity_date,
            'tax_validity_date' => $request->tax_validity_date,
            'fitness_validity_date' => $request->fitness_validity_date,
            'insurance_validity_date' => $request->insurance_validity_date,
            'maker_model' => $request->maker_model,
            'make' => $request->make,
            'model' => $request->model,
            'remarks' => $request->remarks,
            'recommended_by' => $request->recommended_by,
            'referenced' => $request->referenced,
            'local_or_non_local' => $request->local_or_non_local,
            'pan_no' => $request->pan_no,
            'gst_no' => $request->gst_no,
        ];
    }

    private function persistVehicleWorkorderDocuments(VehicleWorkorder $workorder, Request $request): void
    {
        if ($request->hasFile('vehicle_rc_certificate')) {
            $workorder->clearMediaCollection('vehicle_rc');
            $workorder->addMediaFromRequest('vehicle_rc_certificate')->toMediaCollection('vehicle_rc');
        }

        if ($request->hasFile('vehicle_insurance_certificate')) {
            $workorder->clearMediaCollection('vehicle_insurance');
            $workorder->addMediaFromRequest('vehicle_insurance_certificate')->toMediaCollection('vehicle_insurance');
        }

        /** @var array<int, mixed> */
        $otherFilesRaw = $request->file('vehicle_other_documents') ?? [];

        foreach ($otherFilesRaw as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile && $file->isValid()) {
                $workorder->addMedia($file)->toMediaCollection('vehicle_other_documents');
            }
        }
    }

    /**
     * @return array{id: int, label: string}|null
     */
    private function matchedTransportRegistrationForVehicleWorkorder(VehicleWorkorder $workorder, User $user): ?array
    {
        $transport = mb_trim((string) ($workorder->transport_name ?? ''));
        $woNo = mb_trim((string) ($workorder->wo_no ?? ''));
        if ($transport === '' || $woNo === '') {
            return null;
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $vehicleSidingId = (int) $workorder->siding_id;

        $matches = TransportWorkOrderRegistration::query()
            ->where('is_active', true)
            ->where(function (Builder $q) use ($sidingIds): void {
                $q->whereNull('siding_id')
                    ->orWhereIn('siding_id', $sidingIds);
            })
            ->where(function (Builder $q) use ($vehicleSidingId): void {
                $q->whereNull('siding_id')
                    ->orWhere('siding_id', $vehicleSidingId);
            })
            ->whereRaw(
                'LOWER(TRIM(COALESCE(transporter_name, \'\'))) = LOWER(?)',
                [$transport],
            )
            ->whereRaw(
                'LOWER(TRIM(COALESCE(work_order_no_1, \'\'))) = LOWER(?)',
                [$woNo],
            )
            ->orderBy('id')
            ->get();

        if ($matches->count() !== 1) {
            return null;
        }

        /** @var TransportWorkOrderRegistration $registration */
        $registration = $matches->first();

        $label = app(MapTransportRegistrationToVehicleWorkorderDefaults::class)
            ->registrationPickerLabel($registration);

        return [
            'id' => (int) $registration->id,
            'label' => $label,
        ];
    }
}
