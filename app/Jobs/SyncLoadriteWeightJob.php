<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SyncLoadriteEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncLoadriteWeightJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $event
     */
    public function __construct(
        private readonly array $event,
        private readonly int $sidingId,
    ) {}

    public function handle(SyncLoadriteEvent $action): void
    {
        $action->handle($this->event, $this->sidingId);
    }
}
