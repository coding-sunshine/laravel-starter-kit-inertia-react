<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PaymentStage;
use App\Models\Sale;

/**
 * Create a payment stage record for a sale.
 */
final class CreatePaymentStageAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Sale $sale, array $data): PaymentStage
    {
        /** @var PaymentStage $paymentStage */
        $paymentStage = PaymentStage::query()->create([
            'sale_id' => $sale->id,
            'organization_id' => $sale->organization_id,
            'stage' => $data['stage'],
            'amount' => $data['amount'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $paymentStage;
    }
}
