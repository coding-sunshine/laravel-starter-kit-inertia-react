<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\Rake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

final class ReconcilePenaltyHeadsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Rake $rake)
    {
        $this->onQueue('penalties');
    }

    public function handle(ReconcilePenaltyHeadsAction $action): void
    {
        $action->handle($this->rake);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping((string) $this->rake->id))
                ->releaseAfter(30)
                ->expireAfter(120),
        ];
    }
}
