<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Driver;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ListDrivers implements Tool
{
    private const int DEFAULT_LIMIT = 15;

    public function __construct(
        private int $organizationId,
    ) {}

    public function description(): string
    {
        return 'List fleet drivers. Optional filters: status (e.g. active), limit (max number to return).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status: active, inactive, etc.'),
            'limit' => $schema->integer()->description('Max number of drivers to return (default 15)'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = Driver::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId);

        $status = $request['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $limit = (int) ($request['limit'] ?? self::DEFAULT_LIMIT);
        $limit = min(max(1, $limit), 50);

        $drivers = $query->orderBy('last_name')->take($limit)->get(['id', 'first_name', 'last_name', 'employee_id', 'status']);

        if ($drivers->isEmpty()) {
            return 'No drivers found for this organization.';
        }

        $lines = $drivers->map(fn ($d): string => sprintf('#%d %s %s (%s) – %s', $d->id, $d->first_name, $d->last_name, $d->employee_id ?? '—', $d->status));

        return 'Drivers: '."\n".$lines->implode("\n");
    }
}
