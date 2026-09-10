<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

final class StockCapacityIncreasedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{siding_id: int, siding_name?: string|null, closing_balance_mt: float, capacity_rakes: int, requirement_mt: int}  $payload
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
        $sidingName = (string) ($this->payload['siding_name'] ?? '');
        $capacity = (int) ($this->payload['capacity_rakes'] ?? 0);
        $stock = (float) ($this->payload['closing_balance_mt'] ?? 0);
        $req = (int) ($this->payload['requirement_mt'] ?? 3500);

        $title = $sidingName !== ''
            ? "Stock capacity increased ({$sidingName})"
            : 'Stock capacity increased';

        return [
            'type' => 'stock_capacity_increased',
            'title' => $title,
            'message' => "{$title}: can load {$capacity} rake(s) (stock ".number_format($stock, 0)." MT, min {$req} MT/rake).",
            ...$this->payload,
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
