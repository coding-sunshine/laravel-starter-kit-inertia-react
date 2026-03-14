<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Sale;

final readonly class UpdateSaleStageAction
{
    public function __construct(private EvaluateAutomationRulesAction $evaluateRules)
    {
        //
    }

    public function handle(Sale $sale, string $status): void
    {
        $sale->status = $status;
        $sale->status_updated_at = now();
        $sale->save();

        $this->evaluateRules->handle('sale.status_changed', [
            'sale_id' => $sale->id,
            'status' => $status,
        ]);
    }
}
