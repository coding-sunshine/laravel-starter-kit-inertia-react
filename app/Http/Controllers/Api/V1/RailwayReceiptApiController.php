<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RrDocument;
use App\Models\Siding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

final class RailwayReceiptApiController extends Controller
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

        $query = RrDocument::query()
            ->with(['rake.siding'])
            ->whereHas('rake', static function ($q) use ($sidingIds): void {
                $q->whereIn('siding_id', $sidingIds);
            });

        if ($request->filled('siding_id')) {
            $query->whereHas('rake', static function ($q) use ($request): void {
                $q->where('siding_id', $request->integer('siding_id'));
            });
        }

        if ($request->filled('rake_id')) {
            $query->where('rake_id', $request->integer('rake_id'));
        }

        if ($request->filled('rr_number')) {
            $search = $request->string('rr_number')->toString();
            $query->where('rr_number', 'like', "%{$search}%");
        }

        if ($request->filled('document_status')) {
            $query->where('document_status', $request->string('document_status'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('rr_received_date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('rr_received_date', '<=', $request->date('to_date'));
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $items = $paginator->getCollection()->map(static function (RrDocument $doc): array {
            $rake = $doc->rake;

            return [
                'id' => $doc->id,
                'rake_id' => $doc->rake_id,
                'rr_number' => $doc->rr_number,
                'rr_received_date' => $doc->rr_received_date,
                'rr_weight_mt' => $doc->rr_weight_mt,
                'document_status' => $doc->document_status,
                'rake_number' => $rake?->rake_number,
                'siding_name' => $rake?->siding?->name,
                'siding_code' => $rake?->siding?->code,
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

    public function show(Request $request, RrDocument $rrDocument): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $rrDocument->load([
            'rake.siding',
            'rake.wagons',
            'rake.appliedPenalties.penaltyType',
            'rake.appliedPenalties.wagon',
            'rrCharges',
            'wagonSnapshots',
            'penaltySnapshots',
        ]);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if ($rrDocument->rake && ! in_array($rrDocument->rake->siding_id, $sidingIds, true)) {
            abort(403);
        }

        $rake = $rrDocument->rake;

        return response()->json([
            'data' => [
                'header' => [
                    'id' => $rrDocument->id,
                    'rake_id' => $rrDocument->rake_id,
                    'rr_number' => $rrDocument->rr_number,
                    'rr_received_date' => $rrDocument->rr_received_date,
                    'rr_weight_mt' => $rrDocument->rr_weight_mt,
                    'fnr' => $rrDocument->fnr,
                    'document_status' => $rrDocument->document_status,
                    'from_station_code' => $rrDocument->from_station_code,
                    'to_station_code' => $rrDocument->to_station_code,
                    'freight_total' => $rrDocument->freight_total,
                ],
                'rake' => $rake ? [
                    'id' => $rake->id,
                    'rake_number' => $rake->rake_number,
                    'siding' => $rake->siding ? [
                        'id' => $rake->siding->id,
                        'name' => $rake->siding->name,
                        'code' => $rake->siding->code,
                    ] : null,
                ] : null,
                'charges' => $rrDocument->rrCharges->map(static function ($charge): array {
                    return [
                        'id' => $charge->id,
                        'charge_code' => $charge->charge_code,
                        'charge_name' => $charge->charge_name,
                        'amount' => $charge->amount,
                    ];
                })->values(),
                'wagonSnapshots' => $rrDocument->wagonSnapshots->map(static function ($w): array {
                    return [
                        'id' => $w->id,
                        'wagon_sequence' => $w->wagon_sequence,
                        'wagon_number' => $w->wagon_number,
                        'wagon_type' => $w->wagon_type,
                        'pcc_weight_mt' => $w->pcc_weight_mt,
                        'loaded_weight_mt' => $w->loaded_weight_mt,
                        'permissible_weight_mt' => $w->permissible_weight_mt,
                        'overload_weight_mt' => $w->overload_weight_mt,
                    ];
                })->values(),
                'penaltySnapshots' => $rrDocument->penaltySnapshots->map(static function ($p): array {
                    return [
                        'id' => $p->id,
                        'penalty_code' => $p->penalty_code,
                        'amount' => $p->amount,
                        'meta' => $p->meta,
                    ];
                })->values(),
            ],
        ]);
    }

    public function download(Request $request, RrDocument $rrDocument)
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $rrDocument->load('rake.siding');

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if ($rrDocument->rake && ! in_array($rrDocument->rake->siding_id, $sidingIds, true)) {
            abort(403);
        }

        $media = $rrDocument->getFirstMedia('rr_pdf');

        if ($media === null) {
            return response()->json([
                'message' => 'No PDF attached to this Railway Receipt.',
            ], 404);
        }

        if (! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
            return response()->json([
                'message' => 'No PDF attached to this Railway Receipt.',
            ], 404);
        }

        return $media;
    }
}
