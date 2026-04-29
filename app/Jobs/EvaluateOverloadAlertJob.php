<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class EvaluateOverloadAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly array $event, public readonly int $sidingId)
    {
        //
    }

    public function handle(): void
    {
        //
    }
}
