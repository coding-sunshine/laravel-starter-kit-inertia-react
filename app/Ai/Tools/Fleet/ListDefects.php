<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Defect;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ListDefects implements Tool
{
    private const int DEFAULT_LIMIT = 15;

    public function __construct(private int $organizationId) {}

    public function description(): string
    {
        return 'List defects (DVIR-style). Optional: vehicle_id, status (e.g. open, resolved), limit.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'vehicle_id' => $schema->integer()->description('Filter by vehicle ID'),
            'status' => $schema->string()->description('Filter by status'),
            'limit' => $schema->integer()->description('Max to return (default 15)'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = Defect::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->with('vehicle:id,registration')
            ->latest('reported_at');
        if ($vid = $request['vehicle_id'] ?? null) {
            $query->where('vehicle_id', (int) $vid);
        }
        if (is_string($st = $request['status'] ?? null) && $st !== '') {
            $query->where('status', $st);
        }
        $limit = min(max(1, (int) ($request['limit'] ?? self::DEFAULT_LIMIT)), 50);
        $defects = $query->take($limit)->get(['id', 'defect_number', 'title', 'severity', 'status']);
        if ($defects->isEmpty()) {
            return 'No defects found for this organization.';
        }
        $lines = $defects->map(fn ($d): string => sprintf('#%d %s - %s (%s)', $d->id, $d->defect_number, $d->title, $d->severity));

        return 'Defects: '."\n".$lines->implode("\n");
    }
}
