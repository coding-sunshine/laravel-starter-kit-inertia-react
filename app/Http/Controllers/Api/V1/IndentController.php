<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateIndentAndProvisionRakeAction;
use App\DataTables\IndentDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIndentApiRequest;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use App\Services\IndentPdfImporter;
use App\Support\IndentPdfImportScope;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\QueryBuilder\QueryBuilder;

final class IndentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $query = IndentDataTable::listQueryForRequest($request);

        $query->with([
            'siding:id,name,code',
            'rake' => static fn ($q) => $q
                ->select(['id', 'indent_id', 'rake_number', 'rake_serial_number'])
                ->with(['rakeWeighments:id,rake_id']),
        ]);

        $query->when($request->filled('search'), function (Builder $q) use ($request): void {
            $search = $request->string('search')->toString();
            $q->where(function ($inner) use ($search): void {
                $inner->where('indent_number', 'like', "%{$search}%")
                    ->orWhere('e_demand_reference_id', 'like', "%{$search}%")
                    ->orWhere('fnr_number', 'like', "%{$search}%");
            });
        });

        // Mobile-friendly query params mapped as additive filters
        // while preserving canonical DataTable filter[...] behavior.
        $query->when($request->filled('fnr'), function (Builder $q) use ($request): void {
            $q->where('fnr_number', 'like', '%'.$request->string('fnr')->toString().'%');
        });

        $query->when($request->filled('siding_id'), function (Builder $q) use ($request): void {
            $q->where('siding_id', $request->integer('siding_id'));
        });

        $query->when($request->filled('indent_date_start'), function (Builder $q) use ($request): void {
            $q->whereDate('indent_date', '>=', $request->date('indent_date_start'));
        });
        $query->when($request->filled('indent_date_end'), function (Builder $q) use ($request): void {
            $q->whereDate('indent_date', '<=', $request->date('indent_date_end'));
        });

        $query->when($request->filled('expected_loading_start'), function (Builder $q) use ($request): void {
            $q->whereDate('expected_loading_date', '>=', $request->date('expected_loading_start'));
        });
        $query->when($request->filled('expected_loading_end'), function (Builder $q) use ($request): void {
            $q->whereDate('expected_loading_date', '<=', $request->date('expected_loading_end'));
        });

        /** @var LengthAwarePaginator $indents */
        $indents = QueryBuilder::for($query, $request)
            ->allowedFilters(IndentDataTable::tableAllowedFilters())
            ->allowedSorts(IndentDataTable::tableAllowedSorts())
            ->defaultSort('-id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $rows = $indents->through(static function (Indent $indent): array {
            $row = IndentDataTable::fromModel($indent);

            return [
                'id' => $row->id,
                'rake_number' => $row->rake_number,
                'rake_serial_number' => $row->rake_serial_number,
                'siding_code' => $row->siding_code,
                'indent_number' => $row->indent_number,
                'siding' => $row->siding,
                'indent_date' => $row->indent_date,
                'expected_loading_date' => $row->expected_loading_date,
                'e_demand_reference_id' => $row->e_demand_reference_id,
                'fnr_number' => $row->fnr_number,
                'state' => $row->state,
                'weighment_pdf_uploaded' => $row->weighment_pdf_uploaded,
            ];
        });

        return response()->json([
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $indents->currentPage(),
                'per_page' => $indents->perPage(),
                'total' => $indents->total(),
                'last_page' => $indents->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Indent $indent): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        abort_unless(in_array($indent->siding_id, $sidingIds, true), 403);

        $indent->load([
            'siding:id,name,code',
        ]);

        $rake = Rake::where('indent_id', $indent->id)
            ->select('id', 'rake_number', 'state')
            ->first();

        $hasPdf = $indent->getFirstMedia('indent_pdf')
            || $indent->getFirstMedia('indent_confirmation_pdf');

        $indent->setAttribute('indent_pdf_download_url', $hasPdf ? route('indents.pdf', $indent) : null);

        return response()->json([
            'data' => [
                'indent' => $indent,
                'rake' => $rake,
            ],
        ]);
    }

    public function upload(Request $request, IndentPdfImporter $importer): JsonResponse
    {
        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $sidingIds = IndentPdfImportScope::allowedSidingIdsFor($user);

        try {
            $prefill = $importer->previewImport(
                $validated['pdf'],
                $user->id,
                $sidingIds
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'pdf' => [$e->getMessage()],
            ]);
        }

        return response()->json([
            'data' => [
                'prefill' => $prefill,
            ],
            'message' => 'e-Demand PDF parsed. Submit create indent with these values to create the indent and rake.',
        ]);
    }

    public function store(StoreIndentApiRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $rake = app(CreateIndentAndProvisionRakeAction::class)->handle(
            $request,
            $user,
            $request->validated(),
        );

        $rake->loadMissing('indent');
        $indent = $rake->indent;
        abort_unless($indent !== null, 500);

        return response()->json([
            'data' => [
                'indent' => $indent->fresh(),
                'rake' => $rake->fresh(),
            ],
            'message' => 'E-Demand created successfully. The PDF is attached for download.',
        ], 201);
    }

    public function assignRakeNumber(Request $request, Indent $indent): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        if (! $user->isSuperAdmin() && ! $user->canAccessSiding($indent->siding_id)) {
            abort(403);
        }

        $validated = $request->validate(
            [
                'rake_serial_number' => ['required', 'string', 'max:100'],
            ],
            [],
            [
                'rake_serial_number' => 'Rake number',
            ],
        );

        $indent->loadMissing('rake:id,indent_id,siding_id,rake_serial_number,loading_date');
        if ($indent->rake === null) {
            return response()->json([
                'message' => 'This e-Demand does not have a linked rake yet.',
                'errors' => [
                    'rake_serial_number' => ['This e-Demand does not have a linked rake yet.'],
                ],
            ], 422);
        }

        if ($indent->rake->loading_date === null) {
            return response()->json([
                'message' => 'Cannot validate rake number uniqueness because loading date is missing.',
                'errors' => [
                    'rake_serial_number' => ['Cannot validate rake number uniqueness because loading date is missing.'],
                ],
            ], 422);
        }

        $reference = Date::parse($indent->rake->loading_date);
        $trimmedSerial = mb_trim((string) $validated['rake_serial_number']);
        $existsInMonth = Rake::query()
            ->where('rake_serial_number', $trimmedSerial)
            ->where('siding_id', $indent->rake->siding_id)
            ->whereYear('loading_date', $reference->year)
            ->whereMonth('loading_date', $reference->month)
            ->whereKeyNot($indent->rake->id)
            ->exists();

        if ($existsInMonth) {
            return response()->json([
                'message' => 'This rake number is already in use for this siding in the loading month.',
                'errors' => [
                    'rake_serial_number' => ['This rake number is already in use for this siding in the loading month.'],
                ],
            ], 422);
        }

        $indent->rake->rake_serial_number = $trimmedSerial;
        $indent->rake->updated_by = $user->id;
        $indent->rake->save();

        return response()->json([
            'message' => 'Rake number updated.',
            'data' => [
                'rake_serial_number' => $indent->rake->rake_serial_number,
            ],
        ]);
    }

    public function download(Request $request, Indent $indent): Responsable
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        abort_unless(in_array($indent->siding_id, $sidingIds, true), 403);

        $media = $indent->getFirstMedia('indent_pdf')
            ?? $indent->getFirstMedia('indent_confirmation_pdf');

        if (! $media) {
            abort(404, 'No PDF attached to this indent.');
        }

        return $media;
    }
}
