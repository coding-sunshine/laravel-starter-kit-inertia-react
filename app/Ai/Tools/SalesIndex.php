<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Sale;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class SalesIndex implements Tool
{
    public function description(): string
    {
        return 'List sales in the current organization with client, project, lot, and commission totals. Optionally limit by count.';
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = min(max((int) $request->integer('limit', 10), 1), 50);

        $rows = Sale::query()
            ->with(['clientContact:id,first_name,last_name', 'project:id,title', 'lot:id,title'])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'client_contact_id', 'project_id', 'lot_id', 'comms_in_total', 'comms_out_total', 'finance_due_date', 'updated_at']);

        if ($rows->isEmpty()) {
            return 'No sales found.';
        }

        $lines = $rows->map(function (Sale $s): string {
            $client = $s->relationLoaded('clientContact') && $s->clientContact
                ? trim($s->clientContact->first_name.' '.$s->clientContact->last_name)
                : '—';
            $project = $s->relationLoaded('project') ? ($s->project?->title ?? '—') : '—';
            $lot = $s->relationLoaded('lot') ? ($s->lot?->title ?? '—') : '—';
            return sprintf(
                'ID %d: Client %s | Project: %s | Lot: %s | Comm in: %s | Comm out: %s | Finance due: %s | Updated: %s',
                $s->id,
                $client,
                \Illuminate\Support\Str::limit($project, 25),
                \Illuminate\Support\Str::limit($lot, 15),
                $s->comms_in_total ? number_format($s->comms_in_total, 2) : '—',
                $s->comms_out_total ? number_format($s->comms_out_total, 2) : '—',
                $s->finance_due_date?->toDateString() ?? '—',
                $s->updated_at?->toDateString() ?? '—',
            );
        });

        return "Sales (up to {$limit}):\n\n".$lines->implode("\n");
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Max results (1–50).')->default(10),
        ];
    }
}
