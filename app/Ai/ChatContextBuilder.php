<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\Alert;
use App\Models\Indent;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\SidingPerformance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Builds a short, current-data context string for the chat agent
 * so the AI can use up-to-date user, sidings, rakes, alerts, penalties, and indents.
 */
final class ChatContextBuilder
{
    public function build(User $user, ?string $currentPage = null): string
    {
        $parts = [];

        $parts[] = 'User: '.$user->name.' ('.$user->email.')';
        $parts[] = 'Roles: '.$this->userRoles($user);

        $sidings = $this->accessibleSidingsSummary($user);
        if ($sidings !== '') {
            $parts[] = 'Sidings the user can access: '.$sidings;
        }

        $rakeSummary = $this->rakeSummary($user);
        if ($rakeSummary !== '') {
            $parts[] = 'Rakes (current): '.$rakeSummary;
        }

        $sidingIds = $this->sidingIdsForUser($user);
        if ($sidingIds !== []) {
            $alertsSummary = $this->activeAlertsSummary($sidingIds);
            if ($alertsSummary !== '') {
                $parts[] = 'Active alerts: '.$alertsSummary;
            }
            $loadingRakes = $this->loadingRakesWithRemainingTime($sidingIds);
            if ($loadingRakes !== '') {
                $parts[] = 'Rakes in loading (remaining free time): '.$loadingRakes;
            }
            $penaltiesSummary = $this->penaltiesThisMonthSummary($sidingIds);
            if ($penaltiesSummary !== '') {
                $parts[] = 'Penalties this month: '.$penaltiesSummary;
            }
            $indentsSummary = $this->indentsSummary($sidingIds);
            if ($indentsSummary !== '') {
                $parts[] = 'Indents: '.$indentsSummary;
            }
            $penaltyBreakdown = $this->penaltyBreakdownBySiding($sidingIds);
            if ($penaltyBreakdown !== '') {
                $parts[] = 'Penalty breakdown by siding this month: '.$penaltyBreakdown;
            }
            $performanceSummary = $this->sidingPerformanceSummary($sidingIds);
            if ($performanceSummary !== '') {
                $parts[] = 'Siding performance (last 7 days): '.$performanceSummary;
            }
            $penaltyTrend = $this->penaltyTrendDirection($sidingIds);
            if ($penaltyTrend !== '') {
                $parts[] = 'Penalty trend: '.$penaltyTrend;
            }
            $topType = $this->topPenaltyType($sidingIds);
            if ($topType !== '') {
                $parts[] = 'Top penalty type this month: '.$topType;
            }
            $worstSiding = $this->highestPenaltySiding($sidingIds);
            if ($worstSiding !== '') {
                $parts[] = 'Highest penalty siding this month: '.$worstSiding;
            }
            $disputes = $this->recentDisputeOutcomes($sidingIds);
            if ($disputes !== '') {
                $parts[] = 'Recent dispute outcomes: '.$disputes;
            }
        }

        $rate = config('rrmcs.demurrage_rate_per_mt_hour', 50);
        $parts[] = "Demurrage rate: ₹{$rate} per MT per hour. Formula: demurrage = hours over free time × weight (MT) × rate.";

        if ($currentPage !== null && $currentPage !== '') {
            $parts[] = 'User is currently on: '.$currentPage;
        }

        return 'Current context — '.implode('. ', $parts);
    }

    /** @return array<int> */
    private function sidingIdsForUser(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return Siding::query()->pluck('id')->all();
        }

        return $user->accessibleSidings()->get()->pluck('id')->all();
    }

    private function activeAlertsSummary(array $sidingIds): string
    {
        $counts = Alert::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('status', 'active')
            ->select('type', DB::raw('count(*) as c'))
            ->groupBy('type')
            ->pluck('c', 'type')
            ->all();

        if ($counts === []) {
            return 'none';
        }

        $segments = [];
        foreach ($counts as $type => $c) {
            $segments[] = "{$c} {$type}";
        }

        return implode(', ', $segments);
    }

    private function loadingRakesWithRemainingTime(array $sidingIds): string
    {
        $rakes = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('state', 'loading')
            ->whereNotNull('placement_time')
            ->whereNotNull('free_time_minutes')
            ->get(['id', 'rake_number', 'placement_time', 'free_time_minutes']);

        if ($rakes->isEmpty()) {
            return 'none';
        }

        $segments = [];
        foreach ($rakes->take(10) as $rake) {
            $end = $rake->placement_time->copy()->addMinutes((int) $rake->free_time_minutes);
            $remaining = (int) \Illuminate\Support\Facades\Date::now()->diffInMinutes($end, false);
            $segments[] = "{$rake->rake_number}: ".($remaining <= 0 ? 'exceeded' : "{$remaining} min left");
        }
        if ($rakes->count() > 10) {
            $segments[] = '... and '.($rakes->count() - 10).' more';
        }

        return implode('; ', $segments);
    }

    private function penaltiesThisMonthSummary(array $sidingIds): string
    {
        $total = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->sum('penalty_amount');

        $count = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->count();

        if ($count === 0) {
            return 'none';
        }

        $totalFloat = (float) $total;

        return count($sidingIds) > 1
            ? "{$count} penalties, total ₹".number_format($totalFloat, 2)
            : "{$count} penalties, ₹".number_format($totalFloat, 2);
    }

    private function indentsSummary(array $sidingIds): string
    {
        $counts = Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->select('state', DB::raw('count(*) as c'))
            ->groupBy('state')
            ->pluck('c', 'state')
            ->all();

        if ($counts === []) {
            return 'none';
        }

        $segments = [];
        foreach (['pending', 'submitted', 'acknowledged', 'fulfilled'] as $state) {
            if (isset($counts[$state])) {
                $segments[] = "{$counts[$state]} {$state}";
            }
        }
        foreach (array_diff_key($counts, array_flip(['pending', 'submitted', 'acknowledged', 'fulfilled'])) as $state => $c) {
            $segments[] = "{$c} {$state}";
        }

        return implode(', ', $segments);
    }

    private function userRoles(User $user): string
    {
        $roles = $user->roles()->pluck('name')->all();

        return implode(', ', $roles) ?: 'none';
    }

    private function accessibleSidingsSummary(User $user): string
    {
        $query = $user->isSuperAdmin()
            ? Siding::query()->select('code', 'name')
            : $user->sidings()->select('sidings.code', 'sidings.name');

        return $query->get()->map(fn ($s): string => $s->code.' ('.$s->name.')')->take(20)->implode(', ');
    }

    private function rakeSummary(User $user): string
    {
        $query = Rake::query();

        if (! $user->isSuperAdmin()) {
            $sidingIds = $user->sidings()->pluck('sidings.id');
            $query->whereIn('siding_id', $sidingIds);
        }

        $counts = $query
            ->select('state', DB::raw('count(*) as c'))
            ->groupBy('state')
            ->pluck('c', 'state')
            ->all();

        if ($counts === []) {
            return 'no rakes';
        }

        $segments = [];
        foreach (['pending', 'loading', 'loaded', 'dispatched'] as $state) {
            if (isset($counts[$state])) {
                $segments[] = $counts[$state].' '.$state;
            }
        }
        foreach (array_diff_key($counts, array_flip(['pending', 'loading', 'loaded', 'dispatched'])) as $state => $c) {
            $segments[] = $c.' '.$state;
        }

        return implode(', ', $segments);
    }

    /**
     * Per-siding penalty breakdown: top penalty types and total amounts this month.
     *
     * @param  array<int>  $sidingIds
     */
    private function penaltyBreakdownBySiding(array $sidingIds): string
    {
        $rows = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->select(
                'sidings.name as siding_name',
                'penalties.penalty_type',
                DB::raw('count(*) as cnt'),
                DB::raw('sum(penalty_amount) as total')
            )
            ->groupBy('sidings.name', 'penalties.penalty_type')
            ->orderByDesc('total')
            ->toBase()
            ->get();

        if ($rows->isEmpty()) {
            return '';
        }

        $bySiding = [];
        foreach ($rows as $row) {
            $bySiding[$row->siding_name][] = "{$row->cnt} {$row->penalty_type} (₹".number_format((float) $row->total, 0).')';
        }

        $segments = [];
        foreach ($bySiding as $siding => $types) {
            $segments[] = $siding.': '.implode(', ', array_slice($types, 0, 3));
        }

        return implode('; ', $segments);
    }

    /**
     * Penalty trend: up/down vs last month with % change.
     *
     * @param  array<int>  $sidingIds
     */
    private function penaltyTrendDirection(array $sidingIds): string
    {
        $thisMonth = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->sum('penalty_amount');

        $lastMonth = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->subMonth()->month)
            ->whereYear('penalty_date', now()->subMonth()->year)
            ->sum('penalty_amount');

        if ($lastMonth <= 0 && $thisMonth <= 0) {
            return '';
        }

        $pctChange = $lastMonth > 0
            ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 1)
            : 100;

        $direction = $thisMonth > $lastMonth ? 'up' : ($thisMonth < $lastMonth ? 'down' : 'flat');

        return "{$direction} {$pctChange}% vs last month (₹".number_format($thisMonth, 0).' this month, ₹'.number_format($lastMonth, 0).' last month)';
    }

    /**
     * Top penalty type this month by amount.
     *
     * @param  array<int>  $sidingIds
     */
    private function topPenaltyType(array $sidingIds): string
    {
        $top = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->selectRaw('penalty_type, count(*) as cnt, sum(penalty_amount) as total')
            ->groupBy('penalty_type')
            ->orderByDesc('total')
            ->toBase()
            ->first();

        if (! $top) {
            return '';
        }

        return "{$top->penalty_type} ({$top->cnt} incidents, ₹".number_format((float) $top->total, 0).')';
    }

    /**
     * Siding with highest penalties this month.
     *
     * @param  array<int>  $sidingIds
     */
    private function highestPenaltySiding(array $sidingIds): string
    {
        $top = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->selectRaw('sidings.name, count(*) as cnt, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->toBase()
            ->first();

        if (! $top) {
            return '';
        }

        return "{$top->name} ({$top->cnt} penalties, ₹".number_format((float) $top->total, 0).')';
    }

    /**
     * Recent dispute outcomes (last 90 days).
     *
     * @param  array<int>  $sidingIds
     */
    private function recentDisputeOutcomes(array $sidingIds): string
    {
        $disputed = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subDays(90))
            ->where('penalty_status', 'disputed')
            ->count();

        $waived = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subDays(90))
            ->where('penalty_status', 'waived')
            ->count();

        $total = $disputed + $waived;
        if ($total === 0) {
            return '';
        }

        $successRate = round($waived / $total * 100, 0);

        return "{$waived} waived out of {$total} disputed ({$successRate}% success rate)";
    }

    /**
     * Siding performance summary from aggregated table (last 7 days).
     *
     * @param  array<int>  $sidingIds
     */
    private function sidingPerformanceSummary(array $sidingIds): string
    {
        $rows = SidingPerformance::query()
            ->join('sidings', 'siding_performance.siding_id', '=', 'sidings.id')
            ->whereIn('siding_performance.siding_id', $sidingIds)
            ->where('as_of_date', '>=', now()->subDays(7)->toDateString())
            ->select(
                'sidings.name as siding_name',
                DB::raw('sum(rakes_processed) as rakes'),
                DB::raw('sum(total_penalty_amount) as penalties'),
                DB::raw('avg(average_demurrage_hours) as avg_demurrage'),
                DB::raw('sum(penalty_incidents) as incidents')
            )
            ->groupBy('sidings.name')
            ->toBase()
            ->get();

        if ($rows->isEmpty()) {
            return '';
        }

        $segments = [];
        foreach ($rows as $row) {
            $parts = [];
            $parts[] = (int) $row->rakes.' rakes';
            if ((float) $row->penalties > 0) {
                $parts[] = '₹'.number_format((float) $row->penalties, 0).' in penalties ('.(int) $row->incidents.' incidents)';
            }
            if ((float) $row->avg_demurrage > 0) {
                $parts[] = 'avg '.round((float) $row->avg_demurrage).'h demurrage';
            }
            $segments[] = $row->siding_name.': '.implode(', ', $parts);
        }

        return implode('; ', $segments);
    }
}
