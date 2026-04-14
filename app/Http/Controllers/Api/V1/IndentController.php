<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DataTables\IndentDataTable;
use App\Http\Controllers\Controller;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use App\Services\IndentPdfImporter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
                ->select(['id', 'indent_id', 'rake_number', 'state'])
                ->withCount('rakeWeighments'),
        ]);

        $query->when($request->filled('state'), function (Builder $q) use ($request): void {
            $q->where('state', $request->string('state'));
        });

        /** @var array<string, mixed> $filterQuery */
        $filterQuery = $request->query('filter', []);
        $filterQuery = is_array($filterQuery) ? $filterQuery : [];
        $query->when(
            $request->filled('siding_id') && ! array_key_exists('siding', $filterQuery),
            function (Builder $q) use ($request): void {
                $q->where('siding_id', $request->integer('siding_id'));
            }
        );

        $query->when($request->filled('search'), function (Builder $q) use ($request): void {
            $search = $request->string('search')->toString();
            $q->where(function ($inner) use ($search): void {
                $inner->where('indent_number', 'like', "%{$search}%")
                    ->orWhere('e_demand_reference_id', 'like', "%{$search}%")
                    ->orWhere('fnr_number', 'like', "%{$search}%");
            });
        });

        /** @var LengthAwarePaginator $indents */
        $indents = QueryBuilder::for($query, $request)
            ->allowedFilters(IndentDataTable::tableAllowedFilters())
            ->allowedSorts(IndentDataTable::tableAllowedSorts())
            ->defaultSort('-id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $indents->through(function (Indent $indent): Indent {
            $rake = $indent->rake;
            /** True when the rake has any {@see RakeWeighment} (PDF/XLSX import or manual entry; PDF is optional). */
            $indent->setAttribute(
                'weighment_available',
                $rake !== null && (int) ($rake->rake_weighments_count ?? 0) > 0,
            );
            $indent->makeHidden('rake');

            return $indent;
        });

        return response()->json([
            'data' => $indents->items(),
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

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        try {
            $indent = $importer->import(
                $validated['pdf'],
                $user->id,
                $sidingIds
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'pdf' => [$e->getMessage()],
            ]);
        }

        $indent = $indent->fresh(['rake:id,indent_id,rake_number,priority_number,state']);

        return response()->json([
            'data' => [
                'indent' => $indent,
                'rake' => $indent->rake,
            ],
            'message' => 'Indent and rake created from PDF successfully.',
        ], 201);
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
