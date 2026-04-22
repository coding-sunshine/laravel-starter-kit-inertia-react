<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DataTables\WeighmentsRakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\RakeWeighment;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\QueryBuilder;

final class RakeWeighmentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->applyDefaultLoadingDateFilter($request);

        $query = WeighmentsRakeDataTable::tableBaseQuery();

        // Mobile-friendly filters that map to same data as web table.
        $query->when($request->filled('rake_number'), function (Builder $q) use ($request): void {
            $q->where('rake_number', 'like', '%'.$request->string('rake_number')->toString().'%');
        });

        $query->when($request->filled('siding_id'), function (Builder $q) use ($request): void {
            $q->where('siding_id', $request->integer('siding_id'));
        });

        $query->when($request->filled('loading_date_start'), function (Builder $q) use ($request): void {
            $q->whereDate('loading_date', '>=', $request->date('loading_date_start'));
        });
        $query->when($request->filled('loading_date_end'), function (Builder $q) use ($request): void {
            $q->whereDate('loading_date', '<=', $request->date('loading_date_end'));
        });

        /** @var LengthAwarePaginator $paginator */
        $paginator = QueryBuilder::for($query, $request)
            ->allowedFilters(WeighmentsRakeDataTable::tableAllowedFilters())
            ->allowedSorts(WeighmentsRakeDataTable::tableAllowedSorts())
            ->defaultSort(WeighmentsRakeDataTable::tableDefaultSort())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $items = $paginator->getCollection()->map(function (Rake $rake): array {
            $dto = WeighmentsRakeDataTable::fromModel($rake);

            return [
                'id' => $dto->id,
                'rake_number' => $dto->rake_number,
                'rake_serial_number' => $dto->rake_serial_number,
                'indent_number' => $dto->indent_number,
                'loading_date' => $dto->loading_date,
                'siding_id' => $dto->siding_id,
                'siding_code' => $dto->siding_code,
                'siding_name' => $dto->siding_name,
                'destination' => $dto->destination,
                'rake_destination_code' => $dto->rake_destination_code,
                'rake_priority_number' => $dto->rake_priority_number,
                'weighment_row_state' => $dto->weighment_row_state,
                'latest_weighment_id' => $dto->latest_weighment_id,
                'latest_attempt_no' => $dto->latest_attempt_no,
                'latest_total_net_weight_mt' => $dto->latest_total_net_weight_mt,
                'latest_total_gross_weight_mt' => $dto->latest_total_gross_weight_mt,
                'latest_total_tare_weight_mt' => $dto->latest_total_tare_weight_mt,
                'latest_from_station' => $dto->latest_from_station,
                'latest_to_station' => $dto->latest_to_station,
                'latest_priority_number' => $dto->latest_priority_number,
                'latest_wagon_weighments_count' => $dto->latest_wagon_weighments_count,
                'latest_has_pdf_path' => $dto->latest_has_pdf_path,
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, RakeWeighment $rakeWeighment): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $rakeWeighment->load([
            'rake.siding',
            'rakeWagonWeighments.wagon',
        ]);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        abort_unless(in_array($rakeWeighment->rake->siding_id, $sidingIds, true), 403);

        $rake = $rakeWeighment->rake;

        return response()->json([
            'data' => [
                'header' => [
                    'id' => $rakeWeighment->id,
                    'rake_id' => $rakeWeighment->rake_id,
                    'attempt_no' => $rakeWeighment->attempt_no,
                    'gross_weighment_datetime' => $rakeWeighment->gross_weighment_datetime,
                    'tare_weighment_datetime' => $rakeWeighment->tare_weighment_datetime,
                    'train_name' => $rakeWeighment->train_name,
                    'direction' => $rakeWeighment->direction,
                    'commodity' => $rakeWeighment->commodity,
                    'from_station' => $rakeWeighment->from_station,
                    'to_station' => $rakeWeighment->to_station,
                    'priority_number' => $rakeWeighment->priority_number,
                    'status' => $rakeWeighment->status,
                ],
                'totals' => [
                    'total_gross_weight_mt' => $rakeWeighment->total_gross_weight_mt,
                    'total_tare_weight_mt' => $rakeWeighment->total_tare_weight_mt,
                    'total_net_weight_mt' => $rakeWeighment->total_net_weight_mt,
                    'total_cc_weight_mt' => $rakeWeighment->total_cc_weight_mt,
                    'total_under_load_mt' => $rakeWeighment->total_under_load_mt,
                    'total_over_load_mt' => $rakeWeighment->total_over_load_mt,
                    'maximum_train_speed_kmph' => $rakeWeighment->maximum_train_speed_kmph,
                    'maximum_weight_mt' => $rakeWeighment->maximum_weight_mt,
                ],
                'rake' => [
                    'id' => $rake->id,
                    'rake_number' => $rake->rake_number,
                    'rake_serial_number' => $rake->rake_serial_number,
                    'siding_id' => $rake->siding_id,
                    'siding' => [
                        'id' => $rake->siding?->id,
                        'code' => $rake->siding?->code,
                        'name' => $rake->siding?->name,
                    ],
                ],
                'wagons' => $rakeWeighment->rakeWagonWeighments->map(function ($row): array {
                    return [
                        'id' => $row->id,
                        'wagon_id' => $row->wagon_id,
                        'wagon_number' => $row->wagon_number,
                        'wagon_sequence' => $row->wagon_sequence,
                        'wagon_type' => $row->wagon_type,
                        'axles' => $row->axles,
                        'cc_capacity_mt' => $row->cc_capacity_mt,
                        'printed_tare_mt' => $row->printed_tare_mt,
                        'actual_gross_mt' => $row->actual_gross_mt,
                        'actual_tare_mt' => $row->actual_tare_mt,
                        'net_weight_mt' => $row->net_weight_mt,
                        'under_load_mt' => $row->under_load_mt,
                        'over_load_mt' => $row->over_load_mt,
                        'speed_kmph' => $row->speed_kmph,
                        'weighment_time' => $row->weighment_time,
                        'slip_number' => $row->slip_number,
                        'action_taken' => $row->action_taken,
                        'wagon' => $row->wagon ? [
                            'id' => $row->wagon->id,
                            'wagon_number' => $row->wagon->wagon_number,
                            'wagon_sequence' => $row->wagon->wagon_sequence,
                            'wagon_type' => $row->wagon->wagon_type,
                        ] : null,
                    ];
                })->values(),
            ],
        ]);
    }

    public function download(Request $request, RakeWeighment $rakeWeighment)
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $rakeWeighment->load('rake.siding');

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        abort_unless(in_array($rakeWeighment->rake->siding_id, $sidingIds, true), 403);

        $path = $rakeWeighment->pdf_file_path;

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return response()->json([
                'message' => 'Weighment PDF not found.',
            ], 404);
        }

        return Storage::disk('public')->download($path);
    }

    /**
     * Keep web parity: when no loading_date filter is supplied, default to today.
     */
    private function applyDefaultLoadingDateFilter(Request $request): void
    {
        if ($request->filled('loading_date_start') || $request->filled('loading_date_end')) {
            return;
        }

        $filter = $request->input('filter', []);
        if (! is_array($filter)) {
            $filter = [];
        }

        if (array_key_exists('loading_date', $filter)) {
            return;
        }

        $filter['loading_date'] = 'eq:'.now()->toDateString();
        $request->merge(['filter' => $filter]);
    }
}
