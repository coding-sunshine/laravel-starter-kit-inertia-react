<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AiCreditPool;
use App\Models\Billing\Plan;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;
use Throwable;

/**
 * Activates Pennant feature flags for a newly provisioned subscriber
 * based on their plan's features JSON configuration.
 * Also creates the initial AI credit pool for the org.
 */
final readonly class ProvisionSubscriberFeaturesAction
{
    public function handle(User $user, Organization $org, Plan $plan): void
    {
        $features = $plan->features ?? [];
        $flags = (array) ($features['flags'] ?? []);

        foreach ($flags as $featureClass) {
            $fqn = "App\\Features\\{$featureClass}";
            if (! class_exists($fqn)) {
                Log::warning('provision.feature.unknown', ['class' => $fqn]);

                continue;
            }

            try {
                Feature::for($user)->activate($fqn);
            } catch (Throwable $e) {
                Log::error('provision.feature.error', ['feature' => $fqn, 'error' => $e->getMessage()]);
            }
        }

        // Provision AI credits for the org
        $this->provisionAiCredits($org, $plan);
    }

    private function provisionAiCredits(Organization $org, Plan $plan): void
    {
        $credits = (int) ($plan->ai_credits_per_period ?? 50);

        try {
            AiCreditPool::query()->updateOrCreate(
                ['organization_id' => $org->id],
                [
                    'credits_total' => $credits,
                    'credits_used' => 0,
                    'period_start' => Carbon::now()->toDateString(),
                    'period_end' => Carbon::now()->addMonth()->toDateString(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('provision.ai_credits.error', ['org' => $org->id, 'error' => $e->getMessage()]);
        }
    }
}
