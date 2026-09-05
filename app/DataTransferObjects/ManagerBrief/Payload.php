<?php

declare(strict_types=1);

namespace App\DataTransferObjects\ManagerBrief;

use Carbon\CarbonImmutable;

/**
 * The full cached object stored in the manager-brief cache per siding.
 *
 * A Payload is produced by BuildManagerBrief and stored in the application
 * cache for up to 90 minutes. On each page load the controller hydrates this
 * DTO from the cache (via `fromArray`) and passes it to the Inertia page.
 *
 * `ai_status` is either 'ok' (the AI agent succeeded) or 'failed' (the agent
 * threw an exception; `failed_reason` will contain the error message and
 * `model_used` will be null). Consumers should degrade gracefully when
 * `ai_status === 'failed'`, falling back to a deterministic ranked list.
 */
final readonly class Payload
{
    /**
     * @param  list<ActionCard>  $actions  Ranked action cards produced by the AI agent (up to 5)
     */
    public function __construct(
        public array $actions,
        public CarbonImmutable $generatedAt,
        public int $sidingId,
        public ?string $modelUsed,
        public string $aiStatus,
        public ?string $failedReason,
    ) {}

    /**
     * Deserialise from a plain array (e.g. when hydrating from the cache).
     *
     * Parses `generated_at` via CarbonImmutable and rehydrates each action
     * card through ActionCard::fromArray.
     *
     * @param  array{actions: list<array<string, mixed>>, generated_at: string, siding_id: int, model_used: string|null, ai_status: string, failed_reason: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            actions: array_map(
                static fn (array $card): ActionCard => ActionCard::fromArray($card),
                $data['actions'],
            ),
            generatedAt: CarbonImmutable::parse($data['generated_at']),
            sidingId: (int) $data['siding_id'],
            modelUsed: $data['model_used'] ?? null,
            aiStatus: $data['ai_status'],
            failedReason: $data['failed_reason'] ?? null,
        );
    }

    /**
     * Serialise to a plain array for cache storage or Inertia transport.
     *
     * @return array{actions: list<array{severity: string, title: string, why: string, rs_at_stake: float, deep_link: string, deadline: string|null}>, generated_at: string, siding_id: int, model_used: string|null, ai_status: string, failed_reason: string|null}
     */
    public function toArray(): array
    {
        return [
            'actions' => array_map(
                static fn (ActionCard $card): array => $card->toArray(),
                $this->actions,
            ),
            'generated_at' => $this->generatedAt->toIso8601String(),
            'siding_id' => $this->sidingId,
            'model_used' => $this->modelUsed,
            'ai_status' => $this->aiStatus,
            'failed_reason' => $this->failedReason,
        ];
    }
}
