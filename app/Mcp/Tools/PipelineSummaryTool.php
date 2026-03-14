<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\PropertyReservation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Override;

final class PipelineSummaryTool extends Tool
{
    #[Override]
    protected string $name = 'pipeline_summary';

    #[Override]
    protected string $title = 'Pipeline summary';

    #[Override]
    protected string $description = 'Get the reservation pipeline summary grouped by stage. Returns PipelineFunnel data for C1 rendering.';

    public function handle(Request $request): Response
    {
        $projectId = $request->get('project_id');

        $q = PropertyReservation::query();

        if ($projectId !== null) {
            $q->whereHas('lot', fn ($b) => $b->where('project_id', (int) $projectId));
        }

        $stages = $q->selectRaw('state, COUNT(*) as count, SUM(sale_price) as total_value')
            ->groupBy('state')
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

        return Response::json([
            'component' => 'PipelineFunnel',
            'props' => [
                'title' => 'Active Reservation Pipeline',
                'stages' => $stagesData,
                'total_count' => $totalCount,
                'total_value' => $totalValue > 0 ? $totalValue : null,
                'currency' => 'AUD',
            ],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->description('Filter pipeline to a specific project (optional)')->nullable(),
        ];
    }
}
