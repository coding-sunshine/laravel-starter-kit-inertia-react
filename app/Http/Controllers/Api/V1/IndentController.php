<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;
use App\Services\IndentPdfImporter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class IndentController extends Controller
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

        $query = Indent::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->latest('created_at');

        if ($request->filled('from_date')) {
            $query->whereDate('indent_date', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('indent_date', '<=', $request->date('to_date'));
        }

        if ($request->filled('state')) {
            $query->where('state', $request->string('state'));
        }

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->integer('siding_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search): void {
                $q->where('indent_number', 'like', "%{$search}%")
                    ->orWhere('e_demand_reference_id', 'like', "%{$search}%")
                    ->orWhere('fnr_number', 'like', "%{$search}%");
            });
        }

        /** @var LengthAwarePaginator $indents */
        $indents = $query
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

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
