<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exports;

use App\Exports\DispatchReportDprExport;
use App\Http\Controllers\Controller;
use App\Models\DispatchReport;
use App\Models\Siding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DispatchReportDprExportController extends Controller
{
    public function __invoke(Request $request): BinaryFileResponse
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        if (! $user->can('bypass-permissions')
            && ! $user->hasPermissionTo('sections.dashboard.view')
            && ! $user->hasPermissionTo('sections.mines_dispatch_data.view')) {
            abort(403);
        }

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
        ]);

        $hasRange = ! empty($validated['date_from']) && ! empty($validated['date_to']);
        $hasSingle = ! empty($validated['date']) && empty($validated['date_from']) && empty($validated['date_to']);

        if (! $hasRange && ! $hasSingle) {
            throw ValidationException::withMessages([
                'date' => 'Provide either date or both date_from and date_to.',
            ]);
        }

        if ($hasRange && ! empty($validated['date'])) {
            throw ValidationException::withMessages([
                'date' => 'Use either date or date_from/date_to, not both.',
            ]);
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $filterSidingId = $validated['siding_id'] ?? null;
        if ($filterSidingId !== null && ! in_array((int) $filterSidingId, array_map('intval', $sidingIds), true)) {
            abort(403, 'You do not have access to this siding.');
        }

        $query = DispatchReport::with('siding')
            ->whereIn('siding_id', $sidingIds)
            ->when($hasRange, function ($q) use ($validated): void {
                $q->whereDate('issued_on', '>=', $validated['date_from'])
                    ->whereDate('issued_on', '<=', $validated['date_to']);
            })
            ->when($hasSingle, fn ($q) => $q->whereDate('issued_on', $validated['date']))
            ->when($filterSidingId !== null, fn ($q) => $q->where('siding_id', $filterSidingId))
            ->orderBy('issued_on', 'desc')
            ->orderBy('id', 'asc');

        $collection = $query->get();

        $sidingSuffix = 'all';
        if ($filterSidingId !== null) {
            $siding = Siding::query()->find($filterSidingId);
            $sidingSuffix = $siding !== null
                ? preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $siding->code)
                : (string) $filterSidingId;
        }

        if ($hasRange) {
            $from = (string) $validated['date_from'];
            $to = (string) $validated['date_to'];
            $filename = "DPR_{$from}_{$to}_{$sidingSuffix}.xlsx";
        } else {
            $day = (string) $validated['date'];
            $filename = "DPR_{$day}_{$sidingSuffix}.xlsx";
        }

        return Excel::download(new DispatchReportDprExport($collection), $filename);
    }
}
