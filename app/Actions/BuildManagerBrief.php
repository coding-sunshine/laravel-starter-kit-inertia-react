<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\ManagerBrief\CollectSignals;
use App\Actions\ManagerBrief\RankSignals;
use App\Ai\Agents\ManagerBriefAgent;
use App\DataTransferObjects\ManagerBrief\Payload;
use App\DataTransferObjects\ManagerBrief\Signal;
use App\Models\Siding;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the full Manager Brief pipeline for a given siding.
 *
 * Pipeline:
 *   1. CollectSignals   — gather all 7 operational signal types from the DB
 *   2. RankSignals      — score and return the top-15 signals
 *   3. ManagerBriefAgent — synthesise up to 5 ActionCard DTOs via the LLM
 *   4. Compose a Payload DTO with the result (or a failed Payload on AI error)
 *
 * Cache writes are the caller's responsibility — this action is cache-agnostic.
 */
final readonly class BuildManagerBrief
{
    public function __construct(
        private CollectSignals $collect,
        private RankSignals $rank,
        private ManagerBriefAgent $agent,
    ) {}

    /**
     * Run the full pipeline and return a Payload DTO.
     *
     * @param  int  $sidingId  Primary key of the siding to build a brief for
     */
    public function handle(int $sidingId): Payload
    {
        $now = CarbonImmutable::now();

        // Step 1: collect all signals for the siding
        $signals = $this->collect->handle($sidingId);

        // Step 2: rank and take the top 15
        $top15 = $this->rank->handle($signals);

        // Step 3: reuse the previous synthesis when the ranked signals are
        // unchanged — the scheduler refreshes hourly, but overnight the signal
        // set is usually static and each skipped call preserves the free-tier
        // OpenRouter quota (~50 requests/day shared with the chatbot).
        $signalsHash = $this->signalsHash($top15);
        $hashKey = "manager-brief:cards:{$sidingId}:{$signalsHash}";

        /** @var ?list<\App\DataTransferObjects\ManagerBrief\ActionCard> $cards */
        $cards = Cache::get($hashKey);

        if ($cards === null) {
            if ($this->dailyBudgetExhausted()) {
                // Budget spent: fall back to the last successful synthesis
                // (possibly stale) rather than burning further quota.
                $cards = Cache::get("manager-brief:last-cards:{$sidingId}");

                Log::notice('manager-brief: daily AI request budget exhausted', [
                    'siding_id' => $sidingId,
                    'served_stale' => $cards !== null,
                ]);
            } else {
                $this->recordBudgetSpend();

                $cards = $this->agent->synthesise($top15, $this->buildContext($sidingId, $now));

                if ($cards !== null) {
                    Cache::put($hashKey, $cards, CarbonImmutable::now()->addDay());
                    Cache::put("manager-brief:last-cards:{$sidingId}", $cards, CarbonImmutable::now()->addWeek());
                }
            }
        }

        // Step 4: compose the Payload based on outcome
        if ($cards === null) {
            return new Payload(
                actions: [],
                generatedAt: $now,
                sidingId: $sidingId,
                modelUsed: null,
                aiStatus: 'failed',
                failedReason: 'agent_returned_null',
            );
        }

        return new Payload(
            actions: $cards,
            generatedAt: $now,
            sidingId: $sidingId,
            modelUsed: config('prism.defaults.model', 'nvidia/nemotron-3-super-120b-a12b:free'),
            aiStatus: 'ok',
            failedReason: null,
        );
    }

    private static function budgetKey(): string
    {
        return 'ai:auto-requests:'.CarbonImmutable::now()->format('Y-m-d');
    }

    /**
     * Stable hash of the ranked signals, excluding volatile fields.
     *
     * recencyMinutes (and the derived actionability score) drift every run
     * even when nothing operationally changed, so only type, severity,
     * rs-at-stake and the payload participate in the hash.
     *
     * @param  list<Signal>  $top15
     */
    private function signalsHash(array $top15): string
    {
        return md5((string) json_encode(array_map(
            fn (Signal $signal): array => [
                $signal->type,
                $signal->severity,
                $signal->rsAtStake,
                $signal->payload,
            ],
            $top15,
        )));
    }

    /**
     * True when today's automated AI request budget is spent.
     */
    private function dailyBudgetExhausted(): bool
    {
        $limit = (int) config('ai.daily_auto_request_limit', 40);

        return $limit > 0 && (int) Cache::get(self::budgetKey(), 0) >= $limit;
    }

    private function recordBudgetSpend(): void
    {
        $key = self::budgetKey();

        // add() seeds the counter with a TTL; increment() alone would create
        // a key that never expires on some cache stores.
        Cache::add($key, 0, CarbonImmutable::now()->addDay());
        Cache::increment($key);
    }

    /**
     * Build the context array passed to the agent.
     *
     * Includes at minimum siding_id and generated_at (ISO 8601). If the siding
     * record can be loaded cheaply, siding_name is also included.
     *
     * @return array<string, mixed>
     */
    private function buildContext(int $sidingId, CarbonImmutable $now): array
    {
        $context = [
            'siding_id' => $sidingId,
            'generated_at' => $now->toIso8601String(),
        ];

        $siding = Siding::query()->select(['id', 'name'])->find($sidingId);

        if ($siding !== null && ! empty($siding->name)) {
            $context['siding_name'] = (string) $siding->name;
        }

        return $context;
    }
}
