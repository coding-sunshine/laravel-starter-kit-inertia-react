<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\Billing\Credit;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Expire credits that have passed their expiration date.
 *
 * Runs daily to process expired credits and create debit entries.
 */
final class ExpireCredits implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $expiredByEntity = Credit::query()
            ->withoutGlobalScopes()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('amount', '>', 0)
            ->selectRaw('creditable_type, creditable_id, SUM(amount) as total_expired')
            ->groupBy('creditable_type', 'creditable_id')
            ->get();

        $totalExpired = 0;
        $entitiesAffected = 0;
        $errors = 0;

        foreach ($expiredByEntity as $group) {
            try {
                $creditable = $group->creditable_type::find($group->creditable_id);

                if (! $creditable || ! method_exists($creditable, 'expireCredits')) {
                    Log::warning('Creditable entity does not support credit expiration', [
                        'creditable_type' => $group->creditable_type,
                        'creditable_id' => $group->creditable_id,
                    ]);

                    continue;
                }

                $expiredAmount = $creditable->expireCredits();
                $totalExpired += $expiredAmount;
                $entitiesAffected++;

                Log::info('Credits expired for entity', [
                    'creditable_type' => $group->creditable_type,
                    'creditable_id' => $group->creditable_id,
                    'amount' => $expiredAmount,
                ]);
            } catch (Exception $e) {
                $errors++;
                Log::error('Failed to expire credits for entity', [
                    'creditable_type' => $group->creditable_type,
                    'creditable_id' => $group->creditable_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Credit expiration job completed', [
            'total_expired' => $totalExpired,
            'entities_affected' => $entitiesAffected,
            'errors' => $errors,
        ]);
    }
}
