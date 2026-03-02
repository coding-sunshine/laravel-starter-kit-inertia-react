<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class GetVehicle implements Tool
{
    public function __construct(private readonly int $organizationId) {}

    public function description(): string
    {
        return 'Get a single vehicle by ID. Returns registration, make/model, status, MOT/insurance dates, compliance.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Vehicle ID')];
    }

    public function handle(Request $request): string|Stringable
    {
        $id = (int) ($request['id'] ?? 0);
        if ($id <= 0) {
            return 'Please provide a valid vehicle ID.';
        }
        $v = Vehicle::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->find($id);
        if ($v === null) {
            return 'Vehicle not found.';
        }
        $mot = $v->mot_expiry_date?->format('Y-m-d') ?? '—';
        $ins = $v->insurance_expiry_date?->format('Y-m-d') ?? '—';
        return sprintf(
            'Vehicle #%d: %s – %s %s. Status: %s. MOT: %s. Insurance: %s. Compliance: %s. View: /fleet/vehicles/%d',
            $v->id,
            $v->registration,
            $v->make,
            $v->model,
            $v->status,
            $mot,
            $ins,
            $v->compliance_status ?? '—',
            $v->id
        );
    }
}
