<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\ServiceSchedule;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ListServiceSchedules implements Tool
{
    private const int DEFAULT_LIMIT = 15;

    public function __construct(private int $organizationId) {}

    public function description(): string
    {
        return 'List service schedules (next service due). Optional: vehicle_id, due_before (Y-m-d), service_type, limit.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'vehicle_id' => $schema->integer()->description('Filter by vehicle ID'),
            'due_before' => $schema->string()->description('Only due before this date (Y-m-d)'),
            'service_type' => $schema->string()->description('Filter by service type'),
            'limit' => $schema->integer()->description('Max to return (default 15)'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = ServiceSchedule::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->where('is_active', true)
            ->with('vehicle:id,registration')
            ->oldest('next_service_due_date');
        if ($vid = $request['vehicle_id'] ?? null) {
            $query->where('vehicle_id', (int) $vid);
        }
        if (is_string($due = $request['due_before'] ?? null) && $due !== '') {
            $query->whereNotNull('next_service_due_date')->where('next_service_due_date', '<=', $due);
        }
        if (is_string($type = $request['service_type'] ?? null) && $type !== '') {
            $query->where('service_type', $type);
        }
        $limit = min(max(1, (int) ($request['limit'] ?? self::DEFAULT_LIMIT)), 50);
        $schedules = $query->take($limit)->get(['id', 'vehicle_id', 'service_type', 'next_service_due_date']);
        if ($schedules->isEmpty()) {
            return 'No service schedules found for this organization.';
        }
        $lines = $schedules->map(fn ($s): string => sprintf('#%d %s - %s due %s', $s->id, $s->vehicle?->registration ?? '?', $s->service_type, $s->next_service_due_date?->format('Y-m-d') ?? 'not set'));

        return 'Service schedules: '."\n".$lines->implode("\n");
    }
}
