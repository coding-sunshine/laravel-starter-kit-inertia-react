<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class LoadriteOverloadNotification extends Notification
{
    public function __construct(
        private readonly string $level,
        private readonly int $wagonId,
        private readonly string $wagonNumber,
        private readonly int $sidingId,
        private readonly float $weightMt,
        private readonly float $ccMt,
        private readonly float $percentage,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->level === 'warning' ? 'overload_warning' : 'overload_critical',
            'wagon_id' => $this->wagonId,
            'wagon_number' => $this->wagonNumber,
            'siding_id' => $this->sidingId,
            'weight_mt' => $this->weightMt,
            'cc_mt' => $this->ccMt,
            'percentage' => $this->percentage,
            'level' => $this->level,
            'source' => 'loadrite',
        ];
    }
}
