<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Rake;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class DemurrageAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Rake $rake,
        private readonly string $threshold,
        private readonly int $remainingMinutes,
        private readonly float $projectedPenalty,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $sidingName = $this->rake->siding?->name ?? 'Unknown';

        $severity = match ($this->threshold) {
            'demurrage_0' => 'critical',
            'demurrage_30' => 'warning',
            default => 'info',
        };

        return [
            'type' => 'demurrage_alert',
            'rake_id' => $this->rake->id,
            'rake_number' => $this->rake->rake_number,
            'siding_id' => $this->rake->siding_id,
            'siding_name' => $sidingName,
            'threshold' => $this->threshold,
            'remaining_minutes' => $this->remainingMinutes,
            'projected_penalty' => $this->projectedPenalty,
            'severity' => $severity,
            'message' => $this->buildMessage($sidingName, $severity),
        ];
    }

    private function buildMessage(string $sidingName, string $severity): string
    {
        return match ($severity) {
            'critical' => "Demurrage: free time exceeded for rake {$this->rake->rake_number} at {$sidingName}. Penalty accruing.",
            'warning' => "Demurrage: {$this->remainingMinutes} min remaining for rake {$this->rake->rake_number} at {$sidingName}.",
            default => "Demurrage: {$this->remainingMinutes} min remaining for rake {$this->rake->rake_number} at {$sidingName}.",
        };
    }
}
