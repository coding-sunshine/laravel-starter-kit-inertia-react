<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\BillingGatewayContract;
use Illuminate\Support\Str;

/**
 * Stub driver — returns success without charging.
 * Used until eWAY credentials are configured.
 */
final class StubBillingDriver implements BillingGatewayContract
{
    public function createHostedPayment(string $reference, float $amountCents, string $description): array
    {
        $accessCode = 'stub_'.Str::random(16);

        return [
            'redirect_url' => route('billing.stub-return', ['access_code' => $accessCode]),
            'access_code' => $accessCode,
        ];
    }

    public function verifyTransaction(string $accessCode): array
    {
        return [
            'success' => true,
            'transaction_id' => 'stub_txn_'.Str::random(8),
            'message' => 'Stub driver: payment approved',
        ];
    }
}
