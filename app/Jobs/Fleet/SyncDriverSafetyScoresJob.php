<?php

declare(strict_types=1);

namespace App\Jobs\Fleet;

use App\Actions\Fleet\ComputeDriverSafetyScoreAction;
use App\Models\Fleet\Driver;
use App\Services\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncDriverSafetyScoresJob implements ShouldQueue
{
    use Queueable;

    public function handle(ComputeDriverSafetyScoreAction $action): void
    {
        $organizationIds = Driver::query()->distinct()->pluck('organization_id');

        foreach ($organizationIds as $organizationId) {
            TenantContext::set($organizationId);
            Driver::query()->where('organization_id', $organizationId)->each(function (Driver $driver) use ($action): void {
                try {
                    $action->handle($driver);
                } catch (Throwable $e) {
                    Log::warning('Driver safety score computation failed', [
                        'driver_id' => $driver->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }
    }
}
