<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Indent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class IndentStatusTool implements Tool
{
    /**
     * @param  array<int>  $sidingIds
     */
    public function __construct(private readonly array $sidingIds) {}

    public function description(): Stringable|string
    {
        return 'Query indent pipeline by siding and state. Shows pending, submitted, acknowledged, and fulfilled indents. Use this when users ask about indents, e-Demand bookings, or rake placement requests.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = Indent::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $this->sidingIds);

        if ($request['state'] ?? null) {
            $query->where('state', $request['state']);
        }

        $mode = $request['mode'] ?? 'summary';

        if ($mode === 'summary') {
            $byState = (clone $query)
                ->select('state', DB::raw('count(*) as count'))
                ->groupBy('state')
                ->pluck('count', 'state')
                ->all();

            $bySiding = (clone $query)
                ->join('sidings', 'indents.siding_id', '=', 'sidings.id')
                ->select('sidings.name as siding_name', 'state', DB::raw('count(*) as count'))
                ->groupBy('sidings.name', 'state')
                ->toBase()
                ->get()
                ->groupBy('siding_name')
                ->map(fn ($group) => $group->pluck('count', 'state')->all())
                ->all();

            return json_encode([
                'total' => array_sum($byState),
                'by_state' => $byState,
                'by_siding' => $bySiding,
            ], JSON_THROW_ON_ERROR);
        }

        $indents = $query->latest()->limit(20)->get();

        return json_encode([
            'indents' => $indents->map(fn (Indent $i): array => [
                'siding' => $i->siding?->name,
                'state' => $i->state,
                'created_at' => $i->created_at?->toDateTimeString(),
                'updated_at' => $i->updated_at?->toDateTimeString(),
            ])->all(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'state' => $schema->string()->enum(['pending', 'submitted', 'acknowledged', 'fulfilled'])->description('Filter by indent state.'),
            'mode' => $schema->string()->enum(['summary', 'list'])->description('Output mode: "summary" for counts, "list" for detailed records. Defaults to "summary".'),
        ];
    }
}
