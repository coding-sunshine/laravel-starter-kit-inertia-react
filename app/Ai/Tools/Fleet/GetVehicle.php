<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\Scopes\OrganizationScope;
use App\Services\Fleet\GeocodingService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class GetVehicle implements Tool
{
    public function __construct(private readonly int $organizationId) {}

    public function description(): string
    {
        return 'Get a single vehicle by ID. Returns registration, make/model, status, MOT/insurance, compliance, and current position (lat/lng plus human-readable address when available) with last updated time. Use for "Where is [vehicle]?" questions.';
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
        $out = sprintf(
            'Vehicle #%d: %s – %s %s. Status: %s. MOT: %s. Insurance: %s. Compliance: %s.',
            $v->id,
            $v->registration,
            $v->make,
            $v->model,
            $v->status,
            $mot,
            $ins,
            $v->compliance_status ?? '—'
        );
        $hasPosition = $v->current_lat !== null && $v->current_lng !== null;
        if ($hasPosition) {
            $lat = (float) $v->current_lat;
            $lng = (float) $v->current_lng;
            $out .= sprintf(
                ' Location: %.6f, %.6f (last updated %s).',
                $lat,
                $lng,
                $v->location_updated_at?->format('Y-m-d H:i') ?? 'unknown'
            );
            $address = app(GeocodingService::class)->reverseGeocode($lat, $lng);
            if ($address !== null && $address !== '') {
                $out .= ' Address: ' . $address . '.';
            }
        } else {
            $out .= ' No current position reported.';
        }
        $out .= ' View: /fleet/vehicles/' . $v->id;

        return $out;
    }
}
