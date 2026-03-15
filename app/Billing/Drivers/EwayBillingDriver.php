<?php

declare(strict_types=1);

namespace App\Billing\Drivers;

use App\Billing\Contracts\SubscriptionBillingContract;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * eWAY tokenised recurring billing driver stub.
 * Returns success without charging until EWAY_API_KEY / EWAY_API_PASSWORD are set.
 * Actual implementation uses Saloon EwayPaymentConnector for tokenised recurring.
 */
final readonly class EwayBillingDriver implements SubscriptionBillingContract
{
    public function checkout(User $user, Organization $org, string $planSlug, string $successUrl, string $cancelUrl): array
    {
        if (! $this->isConfigured()) {
            Log::info('eway.billing.deferred', ['reason' => 'EWAY_API_KEY not set — activate when credentials provided']);

            return [
                'redirect_url' => $successUrl.'?stub=1&gateway=eway',
                'gateway' => 'eway',
                'session_id' => null,
            ];
        }

        // TODO: Implement eWAY hosted payment page via EwayPaymentConnector (Saloon)
        // Steps:
        // 1. POST /AccessCodes to create AccessCode + FormActionURL
        // 2. Redirect user to FormActionURL with AccessCode
        // 3. On return to $successUrl, verify via verifyTransaction($accessCode)
        // 4. Store eway_token_customer_id on user for recurring billing
        Log::info('eway.billing.checkout', ['org' => $org->id, 'plan' => $planSlug]);

        return [
            'redirect_url' => $successUrl.'?stub=1&gateway=eway',
            'gateway' => 'eway',
            'session_id' => null,
        ];
    }

    public function cancel(User $user, Organization $org): bool
    {
        if (! $this->isConfigured()) {
            Log::info('eway.billing.cancel.deferred');

            return true;
        }

        // TODO: Cancel eWAY tokenised recurring via EwayPaymentConnector
        Log::info('eway.billing.cancel', ['org' => $org->id]);

        return true;
    }

    public function resume(User $user, Organization $org): bool
    {
        if (! $this->isConfigured()) {
            Log::info('eway.billing.resume.deferred');

            return true;
        }

        // TODO: Resume eWAY tokenised recurring subscription
        Log::info('eway.billing.resume', ['org' => $org->id]);

        return true;
    }

    public function getGateway(): string
    {
        return 'eway';
    }

    private function isConfigured(): bool
    {
        return ! empty(config('services.eway.api_key'))
            && ! empty(config('services.eway.api_password'));
    }
}
