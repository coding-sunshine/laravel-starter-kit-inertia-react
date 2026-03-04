<?php

declare(strict_types=1);

namespace App\Events\Fleet;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AiJobCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $organizationId,
        public string $jobType,
        public ?string $entityType = null,
        public ?int $entityId = null,
        public array $resultSummary = [],
    ) {}
}
