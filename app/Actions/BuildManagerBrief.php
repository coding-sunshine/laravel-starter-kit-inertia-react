<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\ManagerBrief\CollectSignals;
use App\Actions\ManagerBrief\RankSignals;
use App\Ai\Agents\ManagerBriefAgent;
use App\DataTransferObjects\ManagerBrief\Payload;
use App\Models\Siding;
use Carbon\CarbonImmutable;

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

        // Step 3: build context for the agent
        $context = $this->buildContext($sidingId, $now);

        // Step 4: call the LLM agent
        $cards = $this->agent->synthesise($top15, $context);

        // Step 5: compose the Payload based on agent outcome
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
            modelUsed: config('prism.defaults.model', 'deepseek/deepseek-r1-0528:free'),
            aiStatus: 'ok',
            failedReason: null,
        );
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
