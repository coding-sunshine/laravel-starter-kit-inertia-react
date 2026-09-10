<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\PenaltyType;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class PenaltiesIndexTool extends Tool
{
    protected string $name = 'penalties_index';

    protected string $title = 'List penalties';

    protected string $description = <<<'MARKDOWN'
        List RR penalty snapshots for the authenticated user's accessible sidings. Supports filters for date range and penalty type.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->pluck('id')->all();

        $query = DB::table('rr_penalty_snapshots as rps')
            ->join('penalty_types as pt', 'pt.code', '=', 'rps.penalty_code')
            ->leftJoin('rakes as r', 'r.id', '=', 'rps.rake_id')
            ->leftJoin('rr_documents as rd', 'rd.id', '=', 'rps.rr_document_id')
            ->whereIn('r.siding_id', $sidingIds);

        if ($request->get('date_from')) {
            $query->whereDate('rd.rr_received_date', '>=', $request->get('date_from'));
        }
        if ($request->get('date_to')) {
            $query->whereDate('rd.rr_received_date', '<=', $request->get('date_to'));
        }
        if ($request->get('penalty_type')) {
            $query->where('rps.penalty_code', $request->get('penalty_type'));
        }

        $perPage = min(50, max(1, (int) ($request->get('per_page') ?? 15)));
        $penalties = $query
            ->select(['rps.id', 'r.rake_number', 'rps.penalty_code', 'pt.name as penalty_name', 'rps.amount', 'rd.rr_received_date'])
            ->orderByDesc('rd.rr_received_date')
            ->paginate($perPage);

        $data = [
            'data' => collect($penalties->items())->map(fn (object $p): array => [
                'id' => $p->id,
                'rake_number' => $p->rake_number,
                'penalty_code' => $p->penalty_code,
                'penalty_name' => $p->penalty_name,
                'amount' => (float) $p->amount,
                'date' => $p->rr_received_date,
            ])->all(),
            'total' => $penalties->total(),
            'per_page' => $penalties->perPage(),
            'current_page' => $penalties->currentPage(),
        ];

        return Response::json($data);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $codes = PenaltyType::query()->pluck('code')->implode(', ');

        return [
            'date_from' => $schema->string()->description('Start date (YYYY-MM-DD)')->nullable(),
            'date_to' => $schema->string()->description('End date (YYYY-MM-DD)')->nullable(),
            'penalty_type' => $schema->string()->description("Filter by penalty type code: {$codes}")->nullable(),
            'per_page' => $schema->integer()->description('Items per page (1-50)')->nullable(),
        ];
    }
}
