<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Alert;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class AlertsTool implements Tool
{
    /**
     * @param  array<int>  $sidingIds
     */
    public function __construct(private readonly array $sidingIds) {}

    public function description(): Stringable|string
    {
        return 'Query active and recent alerts by siding, severity, or type. Use this when users ask about alerts, warnings, demurrage alerts, or notifications.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = Alert::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $this->sidingIds);

        if ($request['status'] ?? null) {
            $query->where('status', $request['status']);
        } else {
            $query->where('status', 'active');
        }

        if ($request['severity'] ?? null) {
            $query->where('severity', $request['severity']);
        }

        if ($request['type'] ?? null) {
            $query->where('type', $request['type']);
        }

        $mode = $request['mode'] ?? 'list';

        if ($mode === 'summary') {
            $byType = (clone $query)
                ->select('type', 'severity', DB::raw('count(*) as count'))
                ->groupBy('type', 'severity')
                ->get()
                ->map(fn ($r): array => [
                    'type' => $r->type,
                    'severity' => $r->severity,
                    'count' => (int) $r->count,
                ])->all();

            return json_encode([
                'total_active' => Alert::query()->whereIn('siding_id', $this->sidingIds)->where('status', 'active')->count(),
                'by_type_severity' => $byType,
            ], JSON_THROW_ON_ERROR);
        }

        $alerts = $query->latest()->limit(20)->get();

        return json_encode([
            'alerts' => $alerts->map(fn (Alert $a): array => [
                'type' => $a->type,
                'severity' => $a->severity,
                'title' => $a->title,
                'body' => $a->body,
                'siding' => $a->siding?->name,
                'status' => $a->status,
                'created_at' => $a->created_at?->toDateTimeString(),
            ])->all(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(['active', 'resolved'])->description('Filter by alert status. Defaults to "active".'),
            'severity' => $schema->string()->enum(['info', 'warning', 'critical'])->description('Filter by severity level.'),
            'type' => $schema->string()->description('Filter by alert type (e.g. demurrage_60, demurrage_30, demurrage_0, overload, rr_mismatch, stock_low).'),
            'mode' => $schema->string()->enum(['list', 'summary'])->description('Output mode: "list" for detailed alerts, "summary" for counts by type/severity. Defaults to "list".'),
        ];
    }
}
