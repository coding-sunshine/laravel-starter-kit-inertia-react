<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Alert;
use App\Models\Indent;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\User;
use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;
use Throwable;

final readonly class GenerateDailyBriefingAction
{
    public function __construct(private PrismService $prism) {}

    /**
     * Generate a short AI briefing for the given user's accessible sidings.
     *
     * @param  array<int>  $sidingIds
     */
    public function handle(User $user, array $sidingIds): ?string
    {
        if ($sidingIds === []) {
            return null;
        }

        $cacheKey = "daily_briefing:{$user->id}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return $cached === '__unavailable__' ? null : $cached;
        }

        if (! $this->prism->isAvailable()) {
            Cache::put($cacheKey, '__unavailable__', 3600);

            return null;
        }

        $data = $this->collectData($sidingIds);
        $prompt = $this->buildPrompt($data);

        try {
            $response = $this->prism->generate($prompt, $this->prism->fastModel());
            $text = mb_trim($response->text);
            Cache::put($cacheKey, $text, 3600);

            return $text;
        } catch (Throwable) {
            Cache::put($cacheKey, '__unavailable__', 900);

            return null;
        }
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function collectData(array $sidingIds): array
    {
        $yesterday = now()->subDay();

        $rakesProcessed = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('loading_end_time')
            ->whereDate('loading_end_time', $yesterday)
            ->count();

        $penaltiesToday = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereDate('penalty_date', $yesterday)
            ->count();

        $penaltyAmount = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereDate('penalty_date', $yesterday)
            ->sum('penalty_amount');

        $thisMonthTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->sum('penalty_amount');

        $lastMonthTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->subMonth()->month)
            ->whereYear('penalty_date', now()->subMonth()->year)
            ->sum('penalty_amount');

        $pendingIndents = Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereIn('state', ['pending', 'submitted'])
            ->count();

        $activeAlerts = Alert::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('status', 'active')
            ->count();

        $loadingRakes = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('state', 'loading')
            ->count();

        return [
            'rakes_processed_yesterday' => $rakesProcessed,
            'penalties_yesterday' => $penaltiesToday,
            'penalty_amount_yesterday' => $penaltyAmount,
            'this_month_penalty_total' => $thisMonthTotal,
            'last_month_penalty_total' => $lastMonthTotal,
            'pending_indents' => $pendingIndents,
            'active_alerts' => $activeAlerts,
            'loading_rakes' => $loadingRakes,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildPrompt(array $data): string
    {
        $trendDirection = $data['this_month_penalty_total'] > $data['last_month_penalty_total'] ? 'increasing' : 'decreasing';
        $pctChange = $data['last_month_penalty_total'] > 0
            ? round(abs($data['this_month_penalty_total'] - $data['last_month_penalty_total']) / $data['last_month_penalty_total'] * 100, 1)
            : 0;

        return <<<PROMPT
        You are RRMCS AI, a railway rake management assistant. Generate a concise daily briefing (3-4 sentences max) based on this operational data:

        Yesterday's operations:
        - Rakes processed: {$data['rakes_processed_yesterday']}
        - Penalties incurred: {$data['penalties_yesterday']} (₹{$data['penalty_amount_yesterday']})
        - Currently loading rakes: {$data['loading_rakes']}
        - Pending indents: {$data['pending_indents']}
        - Active alerts: {$data['active_alerts']}

        Monthly trend: Penalties are {$trendDirection} by {$pctChange}% compared to last month (₹{$data['this_month_penalty_total']} this month vs ₹{$data['last_month_penalty_total']} last month).

        Rules:
        - Be direct and actionable. Start with the most important insight.
        - Mention specific numbers. Use ₹ for currency.
        - If there are loading rakes, remind about monitoring demurrage.
        - If penalties are increasing, flag it as a concern.
        - Keep the tone professional but conversational.
        PROMPT;
    }
}
