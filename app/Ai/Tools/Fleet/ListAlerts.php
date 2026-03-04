<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Alert;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ListAlerts implements Tool
{
    private const int DEFAULT_LIMIT = 10;

    public function __construct(
        private int $organizationId,
    ) {}

    public function description(): string
    {
        return 'List recent or active alerts. Optional: limit, status (e.g. active, acknowledged).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Max number of alerts to return (default 10)'),
            'status' => $schema->string()->description('Filter by status: active, acknowledged, etc.'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = Alert::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->latest('triggered_at');

        $status = $request['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $limit = (int) ($request['limit'] ?? self::DEFAULT_LIMIT);
        $limit = min(max(1, $limit), 30);

        $alerts = $query->take($limit)->get(['id', 'title', 'alert_type', 'severity', 'status', 'triggered_at']);

        if ($alerts->isEmpty()) {
            return 'No alerts found.';
        }

        $lines = $alerts->map(fn ($a): string => sprintf('#%d %s – %s (%s) %s', $a->id, $a->title, $a->alert_type, $a->severity, $a->triggered_at?->format('Y-m-d H:i') ?? ''));

        return 'Alerts: '."\n".$lines->implode("\n");
    }
}
