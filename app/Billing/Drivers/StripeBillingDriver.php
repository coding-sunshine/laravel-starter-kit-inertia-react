<?php

declare(strict_types=1);

namespace App\Billing\Drivers;

use App\Billing\Contracts\SubscriptionBillingContract;
use App\Models\Billing\Plan;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Stripe subscription billing driver.
 * Wraps the kit's PaymentGatewayManager (Stripe) for subscription checkout.
 * Falls back gracefully when STRIPE_SECRET is not configured.
 */
final readonly class StripeBillingDriver implements SubscriptionBillingContract
{
    public function checkout(User $user, Organization $org, string $planSlug, string $successUrl, string $cancelUrl): array
    {
        if (! $this->isConfigured()) {
            Log::info('stripe.billing.deferred', ['reason' => 'STRIPE_SECRET not set']);

            return [
                'redirect_url' => $successUrl.'?stub=1&gateway=stripe',
                'gateway' => 'stripe',
                'session_id' => null,
            ];
        }

        try {
            $plan = Plan::query()->where('slug', $planSlug)->firstOrFail();
            $gatewayPriceId = $plan->getFirstMedia('gateway_price_id')?->getCustomProperty('stripe_price_id');

            if (! $gatewayPriceId) {
                Log::warning('stripe.billing.no_price_id', ['plan' => $planSlug]);

                return [
                    'redirect_url' => $successUrl.'?stub=1&gateway=stripe&note=no_price',
                    'gateway' => 'stripe',
                    'session_id' => null,
                ];
            }

            /** @var \App\Services\PaymentGateway\PaymentGatewayManager $manager */
            $manager = app(\App\Services\PaymentGateway\PaymentGatewayManager::class);
            $sessionId = $manager->createSubscriptionCheckout($org, $gatewayPriceId, $successUrl, $cancelUrl);

            return [
                'redirect_url' => 'https://checkout.stripe.com/pay/'.$sessionId,
                'gateway' => 'stripe',
                'session_id' => $sessionId,
            ];
        } catch (Throwable $e) {
            Log::error('stripe.billing.checkout_error', ['error' => $e->getMessage()]);

            return [
                'redirect_url' => $successUrl.'?stub=1&gateway=stripe&error=1',
                'gateway' => 'stripe',
                'session_id' => null,
            ];
        }
    }

    public function cancel(User $user, Organization $org): bool
    {
        if (! $this->isConfigured()) {
            Log::info('stripe.billing.cancel.deferred');

            return true;
        }

        try {
            $subscription = $org->planSubscriptions()->active()->first();
            if (! $subscription) {
                return false;
            }

            $gatewaySubId = $subscription->gateway_subscription_id;
            if ($gatewaySubId) {
                /** @var \App\Services\PaymentGateway\PaymentGatewayManager $manager */
                $manager = app(\App\Services\PaymentGateway\PaymentGatewayManager::class);
                $manager->cancelSubscription($gatewaySubId);
            }

            $subscription->cancel();

            return true;
        } catch (Throwable $e) {
            Log::error('stripe.billing.cancel_error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function resume(User $user, Organization $org): bool
    {
        if (! $this->isConfigured()) {
            Log::info('stripe.billing.resume.deferred');

            return true;
        }

        try {
            $subscription = $org->planSubscriptions()->cancelled()->first();
            if (! $subscription) {
                return false;
            }

            $gatewaySubId = $subscription->gateway_subscription_id;
            if ($gatewaySubId) {
                /** @var \App\Services\PaymentGateway\PaymentGatewayManager $manager */
                $manager = app(\App\Services\PaymentGateway\PaymentGatewayManager::class);
                $manager->resumeSubscription($gatewaySubId);
            }

            $subscription->activate();

            return true;
        } catch (Throwable $e) {
            Log::error('stripe.billing.resume_error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function getGateway(): string
    {
        return 'stripe';
    }

    private function isConfigured(): bool
    {
        return ! empty(config('stripe.secret'));
    }
}
