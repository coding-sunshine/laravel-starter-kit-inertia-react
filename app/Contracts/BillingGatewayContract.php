<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Gateway contract for one-off deposit payments (eWAY, etc.).
 * Subscriptions remain Stripe via kit billing.
 */
interface BillingGatewayContract
{
    /**
     * Create a hosted payment page and return the redirect URL and access code.
     *
     * @return array{redirect_url: string, access_code: string}
     */
    public function createHostedPayment(string $reference, float $amountCents, string $description): array;

    /**
     * Verify a completed payment transaction by access code.
     *
     * @return array{success: bool, transaction_id: string|null, message: string}
     */
    public function verifyTransaction(string $accessCode): array;
}
