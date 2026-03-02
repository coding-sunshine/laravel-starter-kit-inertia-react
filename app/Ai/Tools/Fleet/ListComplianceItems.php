<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\ComplianceItem;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ListComplianceItems implements Tool
{
    private const DEFAULT_LIMIT = 20;

    public function __construct(
        private readonly int $organizationId,
    ) {}

    public function description(): string
    {
        return 'List compliance items (MOT, insurance, licence, etc.). Optional: expiring_within_days (e.g. 30 for items expiring in next 30 days), compliance_type, status, limit.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'expiring_within_days' => $schema->integer()->description('Only items expiring within this many days'),
            'compliance_type' => $schema->string()->description('Filter by type (e.g. MOT, insurance)'),
            'status' => $schema->string()->description('Filter by status'),
            'limit' => $schema->integer()->description('Max number to return (default 20)'),
        ];
    }

    public function handle(Request $request): string|Stringable
    {
        $query = ComplianceItem::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->orderBy('expiry_date');

        $expiringDays = $request['expiring_within_days'] ?? null;
        if ($expiringDays !== null && $expiringDays !== '') {
            $query->where('expiry_date', '<=', now()->addDays((int) $expiringDays));
            $query->where('expiry_date', '>=', now());
        }

        $complianceType = $request['compliance_type'] ?? null;
        if (is_string($complianceType) && $complianceType !== '') {
            $query->where('compliance_type', $complianceType);
        }

        $status = $request['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $limit = (int) ($request['limit'] ?? self::DEFAULT_LIMIT);
        $limit = min(max(1, $limit), 50);

        $items = $query->take($limit)->get(['id', 'title', 'compliance_type', 'expiry_date', 'status', 'entity_type', 'entity_id']);

        if ($items->isEmpty()) {
            return 'No compliance items found for this organization.';
        }

        $lines = $items->map(fn ($c) => sprintf(
            '#%d %s – %s expires %s (%s)',
            $c->id,
            $c->title,
            $c->compliance_type,
            $c->expiry_date?->format('Y-m-d') ?? '?',
            $c->status
        ));

        return 'Compliance items: '."\n".$lines->implode("\n");
    }
}
