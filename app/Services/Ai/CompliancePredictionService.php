<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\CompliancePredictionAgent;
use App\Models\Fleet\ComplianceItem;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Vehicle;
use App\Models\Scopes\OrganizationScope;
use Laravel\Ai\Responses\StructuredAgentResponse;

final readonly class CompliancePredictionService
{
    public function __construct(
        private CompliancePredictionAgent $agent
    ) {}

    /**
     * Run compliance prediction for an organization. Safe to call from queue.
     *
     * @return array{at_risk_vehicles: array, at_risk_drivers: array}
     */
    public function run(int $organizationId): array
    {
        $context = $this->buildContext($organizationId);
        $response = $this->agent->prompt($context);

        if (! $response instanceof StructuredAgentResponse) {
            return ['at_risk_vehicles' => [], 'at_risk_drivers' => []];
        }

        $s = $response->structured;
        $vehicles = $this->normalizeRiskItems($s['at_risk_vehicles'] ?? [], 'vehicle');
        $drivers = $this->normalizeRiskItems($s['at_risk_drivers'] ?? [], 'driver');

        return ['at_risk_vehicles' => $vehicles, 'at_risk_drivers' => $drivers];
    }

    private function buildContext(int $organizationId): string
    {
        $base = fn ($model) => $model::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId);

        $horizon = \Illuminate\Support\Facades\Date::now()->addDays(90)->toDateString();

        $vehicles = $base(Vehicle::class)->select('id', 'registration', 'make', 'model', 'mot_expiry_date', 'tax_expiry_date', 'insurance_expiry_date')
            ->get()
            ->map(fn ($v): array => [
                'id' => $v->id,
                'registration' => $v->registration,
                'make' => $v->make,
                'model' => $v->model,
                'mot_expiry_date' => $v->mot_expiry_date?->toDateString(),
                'tax_expiry_date' => $v->tax_expiry_date?->toDateString(),
                'insurance_expiry_date' => $v->insurance_expiry_date?->toDateString(),
            ])->toArray();

        $drivers = $base(Driver::class)->select('id', 'first_name', 'last_name', 'license_expiry_date', 'cpc_expiry_date', 'medical_certificate_expiry')
            ->get()
            ->map(fn ($d): array => [
                'id' => $d->id,
                'first_name' => $d->first_name,
                'last_name' => $d->last_name,
                'license_expiry_date' => $d->license_expiry_date?->toDateString(),
                'cpc_expiry_date' => $d->cpc_expiry_date?->toDateString(),
                'medical_certificate_expiry' => $d->medical_certificate_expiry?->toDateString(),
            ])->toArray();

        $complianceItems = $base(ComplianceItem::class)
            ->where('expiry_date', '<=', $horizon)
            ->orderBy('expiry_date')
            ->get(['id', 'entity_type', 'entity_id', 'compliance_type', 'title', 'expiry_date', 'status'])
            ->map(fn ($c): array => [
                'entity_type' => $c->entity_type,
                'entity_id' => $c->entity_id,
                'compliance_type' => $c->compliance_type,
                'title' => $c->title,
                'expiry_date' => $c->expiry_date?->toDateString(),
                'status' => $c->status,
            ])->toArray();

        $data = [
            'vehicles' => $vehicles,
            'drivers' => $drivers,
            'compliance_items_due_90_days' => $complianceItems,
            'today' => \Illuminate\Support\Facades\Date::now()->toDateString(),
        ];

        return "Analyze the following fleet compliance data. Identify vehicles and drivers at risk of missing renewals in the next 30, 60, or 90 days. Return at_risk_vehicles and at_risk_drivers.\n\n".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeRiskItems(mixed $items, string $type): array
    {
        if (! is_array($items)) {
            return [];
        }
        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (empty($item['id'])) {
                continue;
            }
            $out[] = [
                'id' => (int) $item['id'],
                'type' => $type,
                'due_date' => (string) ($item['due_date'] ?? ''),
                'item_description' => (string) ($item['item_description'] ?? ''),
                'recommended_action' => (string) ($item['recommended_action'] ?? ''),
                'risk_level' => $this->normalizeRiskLevel((string) ($item['risk_level'] ?? 'medium')),
            ];
        }

        return $out;
    }

    private function normalizeRiskLevel(string $level): string
    {
        $l = mb_strtolower(mb_trim($level));

        return in_array($l, ['low', 'medium', 'high', 'critical'], true) ? $l : 'medium';
    }
}
