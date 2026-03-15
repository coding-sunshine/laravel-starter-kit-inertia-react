<?php

declare(strict_types=1);

namespace App\Billing\Contracts;

use App\Models\Organization;
use App\Models\User;

/**
 * Contract for subscription billing drivers (Stripe, eWAY tokenised recurring).
 * Both drivers write subscription state to the kit's plan_subscriptions table.
 */
interface SubscriptionBillingContract
{
    /**
     * Initiate a subscription checkout session.
     * Returns an array with redirect_url (or session_id) and gateway identifier.
     *
     * @return array{redirect_url: string, gateway: string, session_id: string|null}
     */
    public function checkout(User $user, Organization $org, string $planSlug, string $successUrl, string $cancelUrl): array;

    /**
     * Cancel an active subscription.
     */
    public function cancel(User $user, Organization $org): bool;

    /**
     * Resume a cancelled (at period end) subscription.
     */
    public function resume(User $user, Organization $org): bool;

    /**
     * Return the gateway identifier (stripe|eway|stub).
     */
    public function getGateway(): string;
}
