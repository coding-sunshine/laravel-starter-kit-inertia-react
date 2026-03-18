<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RakeWeighment;
use App\Models\Siding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

final class RakeWeighmentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $query = RakeWeighment::query()
            ->with(['rake.siding'])
            ->whereHas('rake', function ($q) use ($sidingIds): void {
                $q->whereIn('siding_id', $sidingIds);
            });

        if ($request->filled('from_date')) {
            $query->whereDate('gross_weighment_datetime', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('gross_weighment_datetime', '<=', $request->date('to_date'));
        }

        if ($request->filled('siding_id')) {
            $query->whereHas('rake', function ($q) use ($request): void {
                $q->where('siding_id', $request->integer('siding_id'));
            });
        }

        if ($request->filled('rake_id')) {
            $query->where('rake_id', $request->integer('rake_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $items = $paginator->getCollection()->map(function (RakeWeighment $weighment): array {
            $rake = $weighment->rake;

            return [
                'id' => $weighment->id,
                'rake_id' => $weighment->rake_id,
                'attempt_no' => $weighment->attempt_no,
                'gross_weighment_datetime' => $weighment->gross_weighment_datetime,
                'tare_weighment_datetime' => $weighment->tare_weighment_datetime,
                'status' => $weighment->status,
                'total_gross_weight_mt' => $weighment->total_gross_weight_mt,
                'total_tare_weight_mt' => $weighment->total_tare_weight_mt,
                'total_net_weight_mt' => $weighment->total_net_weight_mt,
                'total_cc_weight_mt' => $weighment->total_cc_weight_mt,
                'total_under_load_mt' => $weighment->total_under_load_mt,
                'total_over_load_mt' => $weighment->total_over_load_mt,
                'maximum_train_speed_kmph' => $weighment->maximum_train_speed_kmph,
                'maximum_weight_mt' => $weighment->maximum_weight_mt,
                'rake_number' => $rake?->rake_number,
                'siding_id' => $rake?->siding_id,
                'siding_code' => $rake?->siding?->code,
                'siding_name' => $rake?->siding?->name,
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
}
