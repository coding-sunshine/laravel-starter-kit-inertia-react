<?php

declare(strict_types=1);

namespace App\Actions\Ai;

use App\Models\Sale;
use App\Services\PrismService;
use Throwable;

final readonly class SuggestDealRisk
{
    public function __construct(private PrismService $prism) {}

    public function handle(Sale $sale): ?string
    {
        if (! $this->prism->isAvailable()) {
            return null;
        }

        $sale->loadMissing(['clientContact', 'project']);

        $client = $sale->clientContact
            ? mb_trim($sale->clientContact->first_name.' '.$sale->clientContact->last_name)
            : '—';
        $project = $sale->project?->title ?? '—';
        $status = $sale->status ?? '—';
        $commIn = $sale->comms_in_total !== null ? number_format($sale->comms_in_total, 2) : '—';
        $commOut = $sale->comms_out_total !== null ? number_format($sale->comms_out_total, 2) : '—';
        $financeDue = $sale->finance_due_date?->toDateString() ?? '—';

        $prompt = <<<PROMPT
            Assess the risk level of this property sale deal. Return the risk level (low, medium, or high) followed by a brief 1-2 sentence reasoning.

            Client: {$client}
            Project: {$project}
            Status: {$status}
            Commission in: {$commIn}
            Commission out: {$commOut}
            Finance due date: {$financeDue}
            PROMPT;

        try {
            $response = $this->prism->generate($prompt);

            return $response->text;
        } catch (Throwable) {
            return null;
        }
    }
}
