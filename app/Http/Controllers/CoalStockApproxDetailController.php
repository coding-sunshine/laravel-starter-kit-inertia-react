<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\CoalStockApproxDetailExport;
use App\Models\CoalStockApproxDetail;
use App\Models\Siding;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CoalStockApproxDetailController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $coalStockDetails = $this->baseDetailsQuery($request)
            ->paginate(15)
            ->withQueryString();

        $sidings = Siding::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $tz = (string) config('app.timezone');
        $today = Carbon::now($tz);

        return Inertia::render('MasterData/DailyStockDetails/Index', [
            'coalStockDetails' => $coalStockDetails,
            'sidings' => $sidings,
            'filters' => $this->filtersForInertia($request),
            'calendar' => [
                'today' => $today->toDateString(),
                'yesterday' => $today->copy()->subDay()->toDateString(),
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $request->validate([
            'siding_id' => ['nullable', 'exists:sidings,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $rows = $this->baseDetailsQuery($request)->get();

        $payload = $this->buildExportPayload($rows, $this->buildExportDateDisplay($request));

        $filename = 'Coal_Stock_Approx_Siding_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new CoalStockApproxDetailExport($payload), $filename);
    }

    public function create(): InertiaResponse
    {
        $sidings = Siding::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MasterData/DailyStockDetails/Create', [
            'sidings' => $sidings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'siding_id' => 'nullable|exists:sidings,id',
            'date' => 'nullable|date',
            'railway_siding_opening_coal_stock' => 'nullable|numeric|min:0',
            'railway_siding_closing_coal_stock' => 'nullable|numeric|min:0',
            'coal_dispatch_qty' => 'nullable|numeric|min:0',
            'no_of_rakes' => 'nullable|string|max:50',
            'rakes_qty' => 'nullable|numeric|min:0',
            'source' => 'nullable|in:manual,system',
            'remarks' => 'nullable|string|max:1000',
        ]);

        CoalStockApproxDetail::create($validated);

        return redirect()->route('master-data.daily-stock-details.index')
            ->with('success', 'Daily stock details created successfully.');
    }

    public function edit(CoalStockApproxDetail $coalStockApproxDetail): InertiaResponse
    {
        $sidings = Siding::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MasterData/DailyStockDetails/Edit', [
            'coalStockDetail' => [
                'id' => $coalStockApproxDetail->id,
                'siding_id' => $coalStockApproxDetail->siding_id,
                'date' => $coalStockApproxDetail->date?->toDateString(),
                'railway_siding_opening_coal_stock' => (float) $coalStockApproxDetail->railway_siding_opening_coal_stock,
                'railway_siding_closing_coal_stock' => (float) $coalStockApproxDetail->railway_siding_closing_coal_stock,
                'coal_dispatch_qty' => (float) $coalStockApproxDetail->coal_dispatch_qty,
                'no_of_rakes' => $coalStockApproxDetail->no_of_rakes,
                'rakes_qty' => (float) $coalStockApproxDetail->rakes_qty,
                'source' => $coalStockApproxDetail->source,
                'remarks' => $coalStockApproxDetail->remarks,
            ],
            'sidings' => $sidings,
        ]);
    }

    public function update(Request $request, CoalStockApproxDetail $coalStockApproxDetail): RedirectResponse
    {
        $validated = $request->validate([
            'siding_id' => 'nullable|exists:sidings,id',
            'date' => 'nullable|date',
            'railway_siding_opening_coal_stock' => 'nullable|numeric|min:0',
            'railway_siding_closing_coal_stock' => 'nullable|numeric|min:0',
            'coal_dispatch_qty' => 'nullable|numeric|min:0',
            'no_of_rakes' => 'nullable|string|max:50',
            'rakes_qty' => 'nullable|numeric|min:0',
            'source' => 'nullable|in:manual,system',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $coalStockApproxDetail->update($validated);

        return redirect()->route('master-data.daily-stock-details.index')
            ->with('success', 'Daily stock details updated successfully.');
    }

    public function destroy(CoalStockApproxDetail $coalStockApproxDetail): RedirectResponse
    {
        $coalStockApproxDetail->delete();

        return redirect()->route('master-data.daily-stock-details.index')
            ->with('success', 'Daily stock details deleted successfully.');
    }

    private static function parseRakeCount(?string $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (preg_match('/-?\d+/', $value, $matches) === 1) {
            return (int) $matches[0];
        }

        return 0;
    }

    private function baseDetailsQuery(Request $request): Builder
    {
        $query = CoalStockApproxDetail::query()
            ->with('siding')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->input('siding_id'));
        }

        $this->applyDateFilters($query, $request);

        return $query;
    }

    private function applyDateFilters(Builder $query, Request $request): void
    {
        $hasDateFromKey = $request->has('date_from');
        $hasDateToKey = $request->has('date_to');

        if (! $hasDateFromKey && ! $hasDateToKey) {
            $yesterday = now(config('app.timezone'))->subDay()->toDateString();
            $query->whereDate('date', $yesterday);

            return;
        }

        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            return;
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }
    }

    /**
     * @return array{siding_id: mixed, date_from: string, date_to: string}
     */
    private function filtersForInertia(Request $request): array
    {
        $hasDateFromKey = $request->has('date_from');
        $hasDateToKey = $request->has('date_to');

        if (! $hasDateFromKey && ! $hasDateToKey) {
            $yesterday = now(config('app.timezone'))->subDay()->toDateString();

            return [
                'siding_id' => $request->input('siding_id'),
                'date_from' => $yesterday,
                'date_to' => $yesterday,
            ];
        }

        return [
            'siding_id' => $request->input('siding_id'),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
        ];
    }

    private function buildExportDateDisplay(Request $request): string
    {
        $hasDateFromKey = $request->has('date_from');
        $hasDateToKey = $request->has('date_to');

        if (! $hasDateFromKey && ! $hasDateToKey) {
            return now(config('app.timezone'))->subDay()->format('d-m-Y');
        }

        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            return 'All';
        }

        $from = $request->filled('date_from')
            ? Carbon::parse((string) $request->input('date_from'), config('app.timezone'))->startOfDay()
            : null;
        $to = $request->filled('date_to')
            ? Carbon::parse((string) $request->input('date_to'), config('app.timezone'))->startOfDay()
            : null;

        if ($from !== null && $to !== null) {
            if ($from->toDateString() === $to->toDateString()) {
                return $from->format('d-m-Y');
            }

            return $from->format('d-m-Y').' to '.$to->format('d-m-Y');
        }

        if ($from !== null) {
            return 'From '.$from->format('d-m-Y');
        }

        return 'Up to '.$to->format('d-m-Y');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, CoalStockApproxDetail>  $collection
     * @return array{
     *     date_display: string,
     *     rows: list<array{
     *         siding: string,
     *         opening: float,
     *         road: float,
     *         no_of_rakes: int,
     *         rakes_qty: float,
     *         closing: float,
     *         remarks: string
     *     }>,
     *     totals: array{
     *         opening: float,
     *         road: float,
     *         no_of_rakes: int,
     *         rakes_qty: float,
     *         closing: float
     *     }
     * }
     */
    private function buildExportPayload($collection, string $dateDisplay): array
    {
        $rows = [];
        $totals = [
            'opening' => 0.0,
            'road' => 0.0,
            'no_of_rakes' => 0,
            'rakes_qty' => 0.0,
            'closing' => 0.0,
        ];

        foreach ($collection as $detail) {
            $opening = (float) ($detail->railway_siding_opening_coal_stock ?? 0);
            $road = (float) ($detail->coal_dispatch_qty ?? 0);
            $rakesQty = (float) ($detail->rakes_qty ?? 0);
            $closing = (float) ($detail->railway_siding_closing_coal_stock ?? 0);
            $rakeCount = self::parseRakeCount($detail->no_of_rakes);

            $rows[] = [
                'siding' => $detail->siding?->name ?? '-',
                'opening' => $opening,
                'road' => $road,
                'no_of_rakes' => $rakeCount,
                'rakes_qty' => $rakesQty,
                'closing' => $closing,
                'remarks' => (string) ($detail->remarks ?? ''),
            ];

            $totals['opening'] += $opening;
            $totals['road'] += $road;
            $totals['no_of_rakes'] += $rakeCount;
            $totals['rakes_qty'] += $rakesQty;
            $totals['closing'] += $closing;
        }

        return [
            'date_display' => $dateDisplay,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
