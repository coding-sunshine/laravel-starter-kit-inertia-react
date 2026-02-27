<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Indent;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\Txr;
use App\Models\VehicleUnload;
use App\Models\Wagon;
use App\Models\Weighment;
use Illuminate\Support\Facades\DB;

final readonly class RunReportAction
{
    public const array REPORT_KEYS = [
        'siding_coal_receipt' => ['name' => 'Siding Coal Receipt', 'description' => 'Shift-wise receipt report'],
        'rake_indent' => ['name' => 'Rake Indent', 'description' => 'Indent history report'],
        'txr' => ['name' => 'Rake Placement & TXR', 'description' => 'TXR performance report'],
        'unfit_wagon' => ['name' => 'Unfit Wagon Details', 'description' => 'Unfit wagon log'],
        'wagon_loading' => ['name' => 'Wagon Loading Data', 'description' => 'Loader-wise loading report'],
        'weighment' => ['name' => 'In-Motion Weighment', 'description' => 'Weighment data report'],
        'loader_vs_weighment' => ['name' => 'Loader vs Weighment', 'description' => 'Overload analysis report'],
        'rake_movement' => ['name' => 'Rake Movement', 'description' => 'Movement delays report'],
        'rr_summary' => ['name' => 'Railway Receipt (RR)', 'description' => 'RR summary report'],
        'penalty_register' => ['name' => 'Penalty Register', 'description' => 'Penalty breakdown report'],
        'daily_operations' => ['name' => 'Daily Operations Summary', 'description' => 'Stock, rakes, alerts overview'],
        'demurrage_analysis' => ['name' => 'Demurrage Analysis', 'description' => 'Demurrage charges by month'],
        'financial_impact' => ['name' => 'Financial Impact', 'description' => 'Revenue impact and savings'],
        'rake_lifecycle' => ['name' => 'Rake Lifecycle', 'description' => 'Rake processing timeline'],
        'indent_fulfillment' => ['name' => 'Indent Fulfillment', 'description' => 'Indent allocation progress'],
    ];

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $key, array $sidingIds, array $params = []): array
    {
        return match ($key) {
            'siding_coal_receipt' => $this->sidingCoalReceipt($sidingIds, $params),
            'rake_indent' => $this->rakeIndent($sidingIds, $params),
            'txr' => $this->txrReport($sidingIds, $params),
            'unfit_wagon' => $this->unfitWagon($sidingIds, $params),
            'wagon_loading' => $this->wagonLoading($sidingIds, $params),
            'weighment' => $this->weighmentReport($sidingIds, $params),
            'loader_vs_weighment' => $this->loaderVsWeighment($sidingIds, $params),
            'rake_movement' => $this->rakeMovement($sidingIds, $params),
            'rr_summary' => $this->rrSummary($sidingIds, $params),
            'penalty_register' => $this->penaltyRegister($sidingIds, $params),
            'daily_operations', 'demurrage_analysis', 'financial_impact', 'rake_lifecycle', 'indent_fulfillment' => $this->delegateToGenerateReports($key, $sidingIds, $params),
            default => [],
        };
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function sidingCoalReceipt(array $sidingIds, array $params): array
    {
        $query = VehicleUnload::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $sidingIds);
        $this->applyDateFilter($query, $params, 'unload_end_time', 'created_at');
        if (! empty($params['siding_id'])) {
            $query->where('siding_id', $params['siding_id']);
        }
        $groupByShift = ! empty($params['by_shift']);
        $select = 'siding_id, date(COALESCE(unload_end_time, created_at)) as dt, count(*) as cnt, sum(COALESCE(weighment_weight_mt, mine_weight_mt, 0)) as total_mt';
        if ($groupByShift) {
            $select .= ', COALESCE(shift, \'unspecified\') as shift';
            $query->selectRaw($select)->groupByRaw('siding_id, date(COALESCE(unload_end_time, created_at)), COALESCE(shift, \'unspecified\')')->orderBy('dt')->orderBy('siding_id')->orderBy('shift');
        } else {
            $query->selectRaw($select)->groupBy('siding_id', DB::raw('date(COALESCE(unload_end_time, created_at))'))->orderBy('dt')->orderBy('siding_id');
        }
        $rows = $query->get();

        return $rows->map(fn ($r): array => [
            'siding_id' => $r->siding_id,
            'date' => $r->dt,
            'shift' => $groupByShift ? ($r->shift ?? 'unspecified') : null,
            'unload_count' => (int) $r->cnt,
            'total_mt' => round((float) $r->total_mt, 2),
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rakeIndent(array $sidingIds, array $params): array
    {
        $query = Indent::query()->with('siding:id,name')->whereIn('siding_id', $sidingIds);
        $this->applyDateFilter($query, $params, 'created_at');
        if (! empty($params['siding_id'])) {
            $query->where('siding_id', $params['siding_id']);
        }
        $rows = $query->latest()->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'id' => $r->id,
            'siding' => $r->siding?->name,
            'state' => $r->state,
            'created_at' => $r->created_at->toIso8601String(),
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function txrReport(array $sidingIds, array $params): array
    {
        $query = Txr::query()
            ->with('rake.siding:id,name')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'inspection_time');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->orderByDesc('inspection_time')->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'rake_number' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
            'inspection_time' => $r->inspection_time?->toIso8601String(),
            'state' => $r->state,
            'unfit_wagons_count' => $r->unfit_wagons_count,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function unfitWagon(array $sidingIds, array $params): array
    {
        $query = Wagon::query()
            ->with('rake.siding:id,name')
            ->where('is_unfit', true)
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->latest()->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'wagon_number' => $r->wagon_number,
            'rake_number' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function wagonLoading(array $sidingIds, array $params): array
    {
        $query = Wagon::query()
            ->with('rake.siding:id,name', 'loader:id,loader_name')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        if (! empty($params['date_from']) || ! empty($params['date_to'])) {
            $query->whereHas('rake', function ($q) use ($params): void {
                if (isset($params['date_from']) && ($params['date_from'] !== '' && $params['date_from'] !== '0')) {
                    $q->where('placement_time', '>=', $params['date_from']);
                }
                if (isset($params['date_to']) && ($params['date_to'] !== '' && $params['date_to'] !== '0')) {
                    $q->where('placement_time', '<=', $params['date_to'].' 23:59:59');
                }
            });
        }
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->latest('wagons.created_at')->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'wagon_number' => $r->wagon_number,
            'rake_number' => $r->rake?->rake_number,
            'loader' => $r->loader?->loader_name,
            'loader_qty_mt' => $r->loader_recorded_qty_mt,
            'weighment_qty_mt' => $r->weighment_qty_mt,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function weighmentReport(array $sidingIds, array $params): array
    {
        $query = Weighment::query()
            ->with('rake.siding:id,name')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'weighment_time');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->orderByDesc('weighment_time')->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'rake_number' => $r->rake?->rake_number,
            'weighment_time' => $r->weighment_time?->toIso8601String(),
            'total_weight_mt' => $r->total_weight_mt,
            'weighment_status' => $r->weighment_status,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function loaderVsWeighment(array $sidingIds, array $params): array
    {
        $query = Wagon::query()
            ->with('rake.siding:id,name', 'loader:id,loader_name')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereNotNull('loader_recorded_qty_mt')
            ->whereNotNull('weighment_qty_mt');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['date_from']) || ! empty($params['date_to'])) {
            $query->whereHas('rake', function ($q) use ($params): void {
                if (isset($params['date_from']) && ($params['date_from'] !== '' && $params['date_from'] !== '0')) {
                    $q->where('dispatch_time', '>=', $params['date_from']);
                }
                if (isset($params['date_to']) && ($params['date_to'] !== '' && $params['date_to'] !== '0')) {
                    $q->where('dispatch_time', '<=', $params['date_to'].' 23:59:59');
                }
            });
        }
        $rows = $query->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'wagon_number' => $r->wagon_number,
            'rake_number' => $r->rake?->rake_number,
            'loader_qty' => (float) $r->loader_recorded_qty_mt,
            'weighment_qty' => (float) $r->weighment_qty_mt,
            'variance' => round((float) $r->loader_recorded_qty_mt - (float) $r->weighment_qty_mt, 2),
            'is_overloaded' => $r->is_overloaded,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rakeMovement(array $sidingIds, array $params): array
    {
        $query = Rake::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('placement_time')
            ->whereNotNull('dispatch_time');
        $this->applyDateFilter($query, $params, 'dispatch_time');
        if (! empty($params['siding_id'])) {
            $query->where('siding_id', $params['siding_id']);
        }
        $rows = $query->orderByDesc('dispatch_time')->limit(500)->get();

        return $rows->map(function ($r): array {
            $start = $r->placement_time;
            $end = $r->dispatch_time;
            $minutes = $start && $end ? $start->diffInMinutes($end) : null;

            return [
                'rake_number' => $r->rake_number,
                'siding' => $r->siding?->name,
                'loading_start' => $start?->toIso8601String(),
                'loading_end' => $end?->toIso8601String(),
                'duration_minutes' => $minutes,
            ];
        })->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rrSummary(array $sidingIds, array $params): array
    {
        $query = RrDocument::query()
            ->with('rake.siding:id,name')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'rr_received_date');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->latest('rr_received_date')->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'rr_number' => $r->rr_number,
            'rake_number' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
            'rr_received_date' => $r->rr_received_date?->toDateString(),
            'rr_weight_mt' => $r->rr_weight_mt,
            'document_status' => $r->document_status,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function penaltyRegister(array $sidingIds, array $params): array
    {
        $query = Penalty::query()
            ->with('rake.siding:id,name')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'penalty_date');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->latest('penalty_date')->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'rake_number' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
            'penalty_type' => $r->penalty_type,
            'penalty_amount' => $r->penalty_amount,
            'penalty_status' => $r->penalty_status,
            'penalty_date' => $r->penalty_date?->toDateString(),
        ])->values()->all();
    }

    /**
     * Delegate to the GenerateReports action for rich analytical reports.
     * Returns flattened summary rows suitable for CSV export.
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function delegateToGenerateReports(string $key, array $sidingIds, array $params): array
    {
        $generator = resolve(GenerateReports::class);
        $sidingId = $params['siding_id'] ?? ($sidingIds[0] ?? null);

        if ($sidingId === null) {
            return [];
        }

        $result = match ($key) {
            'daily_operations' => $generator->dailyOperationsSummary((int) $sidingId),
            'demurrage_analysis' => $generator->demurrageAnalysisReport((int) $sidingId),
            'financial_impact' => $generator->financialImpactReport((int) $sidingId),
            'rake_lifecycle' => $generator->rakeLifecycleReport((int) $sidingId),
            'indent_fulfillment' => $generator->indentFulfillmentReport((int) $sidingId),
            default => [],
        };

        // Wrap structured report in a single-row array for consistent CSV export
        if (! is_array($result) || ! array_is_list($result)) {
            return [$result];
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     */
    private function applyDateFilter($query, array $params, string $column, ?string $fallback = null): void
    {
        if (! empty($params['date_from'])) {
            if ($fallback !== null) {
                $query->whereRaw('COALESCE('.$column.', '.$fallback.') >= ?', [$params['date_from']]);
            } else {
                $query->where($column, '>=', $params['date_from']);
            }
        }
        if (! empty($params['date_to'])) {
            $end = $params['date_to'].' 23:59:59';
            if ($fallback !== null) {
                $query->whereRaw('COALESCE('.$column.', '.$fallback.') <= ?', [$end]);
            } else {
                $query->where($column, '<=', $end);
            }
        }
    }
}
