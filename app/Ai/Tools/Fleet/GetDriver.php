<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Driver;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class GetDriver implements Tool
{
    public function __construct(private int $organizationId) {}

    public function description(): string
    {
        return 'Get a single driver by ID. Returns name, status, licence, current vehicle.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Driver ID')];
    }

    public function handle(Request $request): string
    {
        $id = (int) ($request['id'] ?? 0);
        if ($id <= 0) {
            return 'Please provide a valid driver ID.';
        }
        $d = Driver::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->with('currentAssignment.vehicle:id,registration')
            ->find($id);
        if ($d === null) {
            return 'Driver not found.';
        }
        $vehicle = $d->currentAssignment?->vehicle?->registration ?? '—';
        $safety = $d->safety_score !== null ? sprintf(' Safety score: %s/100 (%s).', $d->safety_score, $d->risk_category ?? '—') : '';

        return sprintf(
            'Driver #%d: %s %s. Status: %s. Licence: %s. Current vehicle: %s.%s View: /fleet/drivers/%d',
            $d->id,
            $d->first_name,
            $d->last_name,
            $d->status,
            $d->license_number ?? '—',
            $vehicle,
            $safety,
            $d->id,
        );
    }
}
