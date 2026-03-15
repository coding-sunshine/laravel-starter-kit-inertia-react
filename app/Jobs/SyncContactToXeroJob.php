<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SyncContactToXeroAction;
use App\Models\Contact;
use App\Models\XeroConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncContactToXeroJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly Contact $contact,
        public readonly XeroConnection $connection
    ) {}

    public function handle(SyncContactToXeroAction $action): void
    {
        $action->handle($this->contact, $this->connection);
    }
}
