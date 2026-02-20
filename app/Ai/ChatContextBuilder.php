<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\Alert;
use App\Models\Indent;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\Siding;
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
            ->whereNotNull('loading_start_time')
            ->whereNotNull('free_time_minutes')
            ->get(['id', 'rake_number', 'loading_start_time', 'free_time_minutes']);

        if ($rakes->isEmpty()) {
            return 'none';
        }

        $segments = [];
        foreach ($rakes->take(10) as $rake) {
            $end = $rake->loading_start_time->copy()->addMinutes((int) $rake->free_time_minutes);
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
}
