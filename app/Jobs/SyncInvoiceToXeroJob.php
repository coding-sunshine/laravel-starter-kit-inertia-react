<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\SyncInvoiceToXeroAction;
use App\Models\Sale;
use App\Models\XeroConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SyncInvoiceToXeroJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly Sale $sale,
        public readonly XeroConnection $connection
    ) {}

    public function handle(SyncInvoiceToXeroAction $action): void
    {
        $action->handle($this->sale, $this->connection);
    }
}
