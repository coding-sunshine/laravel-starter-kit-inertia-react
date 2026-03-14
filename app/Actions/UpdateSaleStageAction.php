<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Sale;

final readonly class UpdateSaleStageAction
{
    public function __construct(
        private EvaluateAutomationRulesAction $evaluateRules,
        private TriggerWebhooksAction $triggerWebhooks,
    ) {}

    public function handle(Sale $sale, string $status): void
    {
        $previousStatus = $sale->status;

        $sale->status = $status;
        $sale->status_updated_at = now();
        $sale->save();

        $this->evaluateRules->handle('sale.status_changed', [
            'sale_id' => $sale->id,
            'status' => $status,
        ]);

        if ($previousStatus !== $status && $sale->organization_id !== null) {
            $this->triggerWebhooks->handle('sale.updated', [
                'sale_id' => $sale->id,
                'status' => $status,
                'previous_status' => $previousStatus,
                'organization_id' => $sale->organization_id,
            ], $sale->organization_id);
        }
    }
}
