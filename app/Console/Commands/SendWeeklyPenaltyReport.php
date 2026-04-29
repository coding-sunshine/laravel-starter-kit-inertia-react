<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\WeeklyPenaltyReportReady;
use App\Models\Penalty;
use App\Models\User;
use Illuminate\Console\Command;

final class SendWeeklyPenaltyReport extends Command
{
    protected $signature = 'rrmcs:send-weekly-penalty-report';

    protected $description = 'Dispatch the WeeklyPenaltyReportReady event with last 7-day penalty summary.';

    public function handle(): int
    {
        $this->info('Gathering last 7 days of penalty data…');

        $thisWeek = $this->queryWeekData(0);
        $priorWeek = $this->queryWeekData(1);

        $vsInr = $thisWeek['total_inr'] - $priorWeek['total_inr'];
        $vsPct = $priorWeek['total_inr'] > 0
            ? round(($vsInr / $priorWeek['total_inr']) * 100, 1)
            : 0.0;

        $preventable = $thisWeek['total_count'] > 0
            ? round(($thisWeek['preventable_count'] / $thisWeek['total_count']) * 100, 1)
            : 0.0;

        $reportData = [
            'period_label' => now()->subDays(7)->format('d M Y').' – '.now()->subDay()->format('d M Y'),
            'total_penalties_inr' => $thisWeek['total_inr'],
            'total_penalties_count' => $thisWeek['total_count'],
            'preventable_percent' => $preventable,
            'top_operators' => $thisWeek['top_operators'],
            'vs_prior_week_inr' => $vsInr,
            'vs_prior_week_percent' => $vsPct,
            'sidings_summary' => $thisWeek['sidings_summary'],
        ];

        $recipientEmails = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'admin']))
            ->pluck('email')
            ->all();

        if ($recipientEmails === []) {
            $this->warn('No eligible recipients found. Skipping dispatch.');

            return self::SUCCESS;
        }

        WeeklyPenaltyReportReady::dispatch($reportData, $recipientEmails);

        $this->info('WeeklyPenaltyReportReady dispatched to '.count($recipientEmails).' recipients.');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *   total_inr: float,
     *   total_count: int,
     *   preventable_count: int,
     *   top_operators: list<array{name: string, amount_inr: float}>,
     *   sidings_summary: list<array{siding_name: string, total_inr: float, count: int}>,
     * }
     */
    private function queryWeekData(int $weeksAgo): array
    {
        $from = now()->subWeeks($weeksAgo + 1)->startOfWeek();
        $to = now()->subWeeks($weeksAgo)->startOfWeek();

        $base = Penalty::query()
            ->whereNull('penalties.deleted_at')
            ->where('penalty_date', '>=', $from)
            ->where('penalty_date', '<', $to);

        $totals = $base->clone()
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(penalty_amount), 0) as total_inr')
            ->first();

        $preventable = $base->clone()
            ->whereIn('penalty_status', ['waived', 'disputed'])
            ->count();

        $topOperators = $base->clone()
            ->whereNotNull('responsible_party')
            ->selectRaw('responsible_party as name, SUM(penalty_amount) as amount_inr')
            ->groupBy('responsible_party')
            ->orderByDesc('amount_inr')
            ->limit(3)
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->name,
                'amount_inr' => (float) $r->amount_inr,
            ])
            ->all();

        $sidings = $base->clone()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->selectRaw('sidings.name as siding_name, COUNT(*) as count, SUM(penalties.penalty_amount) as total_inr')
            ->groupBy('sidings.name')
            ->orderByDesc('total_inr')
            ->limit(5)
            ->get()
            ->map(fn ($r): array => [
                'siding_name' => (string) $r->siding_name,
                'total_inr' => (float) $r->total_inr,
                'count' => (int) $r->count,
            ])
            ->all();

        return [
            'total_inr' => (float) ($totals->total_inr ?? 0),
            'total_count' => (int) ($totals->total_count ?? 0),
            'preventable_count' => $preventable,
            'top_operators' => $topOperators,
            'sidings_summary' => $sidings,
        ];
    }
}
