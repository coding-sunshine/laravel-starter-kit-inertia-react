<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\PropertyReservation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * AI tool: get pipeline funnel summary for C1 PipelineFunnel rendering.
 */
final class PipelineSummaryTool implements Tool
{
    public function description(): string
    {
        return 'Get the reservation/sales pipeline summary grouped by stage. Returns PipelineFunnel data for C1 rendering.';
    }

    public function handle(Request $request): Stringable|string
    {
        $projectId = $request->input['project_id'] ?? null;

        $q = PropertyReservation::query();
        if ($projectId !== null) {
            $q->whereHas('lot', fn ($b) => $b->where('project_id', (int) $projectId));
        }

        $stages = $q->selectRaw('state, COUNT(*) as count, SUM(sale_price) as total_value')
            ->groupBy('state')
            ->orderByRaw("ARRAY_POSITION(ARRAY['enquiry','qualified','reservation','unconditional','contract','settled'], state::text)")
            ->get();

        $stageMap = [
            'enquiry' => 'Enquiry',
            'qualified' => 'Qualified',
            'reservation' => 'Reservation',
            'unconditional' => 'Unconditional',
            'contract' => 'Contract',
            'settled' => 'Settled',
        ];

        $stagesData = $stages->map(fn ($s) => [
            'name' => $stageMap[$s->state] ?? $s->state,
            'count' => (int) $s->count,
            'value' => $s->total_value !== null ? (float) $s->total_value : null,
        ])->values()->all();

        $totalCount = array_sum(array_column($stagesData, 'count'));
        $totalValue = array_sum(array_filter(array_column($stagesData, 'value')));

        $result = [
            'component' => 'PipelineFunnel',
            'props' => [
                'title' => 'Active Reservation Pipeline',
                'stages' => $stagesData,
                'total_count' => $totalCount,
                'total_value' => $totalValue > 0 ? $totalValue : null,
                'currency' => 'AUD',
            ],
        ];

        return Str::of(json_encode($result) ?: '{}');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Filter pipeline to a specific project ID (optional)'),
        ];
    }
}
