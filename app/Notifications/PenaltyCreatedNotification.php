<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

final class PenaltyCreatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{rake_id?: int|null, rake_number?: string|null, siding_id?: int|null, siding_name?: string|null, rr_document_id?: int|null, amount_total: float, breakdown?: array<int, array{code: string, amount: float}>, source: string}  $payload
     */
    public function __construct(
        private readonly array $payload,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $amount = (float) ($this->payload['amount_total'] ?? 0);
        $rakeNumber = (string) ($this->payload['rake_number'] ?? '');
        $sidingName = (string) ($this->payload['siding_name'] ?? '');
        $source = (string) ($this->payload['source'] ?? 'unknown');

        $title = $rakeNumber !== ''
            ? "New penalty for rake {$rakeNumber}"
            : 'New penalty recorded';

        $context = mb_trim($sidingName !== '' ? " at {$sidingName}" : '');

        return [
            'type' => 'penalty_created',
            'source' => $source, // demurrage|weighment|rr_snapshot
            'title' => $title,
            'message' => "{$title}{$context}. Total ₹".number_format($amount, 2),
            ...$this->payload,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
