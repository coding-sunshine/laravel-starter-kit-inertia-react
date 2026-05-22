<?php

declare(strict_types=1);

namespace App\DataTransferObjects\ManagerBrief;

/**
 * Represents a single AI-generated action card in the Manager Brief.
 *
 * Action cards are the output of ManagerBriefAgent. Each card corresponds to
 * one ranked, narrated recommendation a manager should act on. The agent
 * produces up to five cards per brief, ordered by combined severity and
 * financial impact.
 *
 * Allowed `severity` values: 'critical', 'high', 'medium', 'low'.
 */
final readonly class ActionCard
{
    public function __construct(
        public string $severity,
        public string $title,
        public string $why,
        public float $rsAtStake,
        public string $deepLink,
        public ?string $deadline,
    ) {}

    /**
     * Deserialise from a plain array (e.g. when hydrating from cache).
     *
     * @param  array{severity: string, title: string, why: string, rs_at_stake: float, deep_link: string, deadline: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            severity: $data['severity'],
            title: $data['title'],
            why: $data['why'],
            rsAtStake: (float) $data['rs_at_stake'],
            deepLink: $data['deep_link'],
            deadline: $data['deadline'] ?? null,
        );
    }

    /**
     * Serialise to a plain array for Inertia transport or cache storage.
     *
     * @return array{severity: string, title: string, why: string, rs_at_stake: float, deep_link: string, deadline: string|null}
     */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'title' => $this->title,
            'why' => $this->why,
            'rs_at_stake' => $this->rsAtStake,
            'deep_link' => $this->deepLink,
            'deadline' => $this->deadline,
        ];
    }
}
