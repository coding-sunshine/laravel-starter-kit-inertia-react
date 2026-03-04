<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\Alert;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateAlertFromPromptAction
{
    /** Alert type enum values from alerts table. */
    private const array ALERT_TYPES = [
        'compliance_expiry', 'maintenance_due', 'defect_reported',
        'incident_reported', 'behavior_violation', 'fuel_anomaly',
        'cost_threshold', 'geofence_violation', 'speed_violation',
        'working_time_violation', 'system_error',
    ];

    /** Severity enum values. */
    private const array SEVERITIES = ['info', 'warning', 'critical', 'emergency'];

    /**
     * Create an alert from AI/conversation input. Validates org and optional entity.
     *
     * @param  array{title: string, description: string, alert_type?: string, severity?: string, entity_type?: string, entity_id?: int}  $input
     */
    public function handle(int $organizationId, int $userId, array $input): Alert
    {
        $title = mb_trim($input['title'] ?? '');
        $description = mb_trim($input['description'] ?? '');
        throw_if($title === '' || $description === '', InvalidArgumentException::class, 'Title and description are required.');

        $alertType = isset($input['alert_type']) && in_array($input['alert_type'], self::ALERT_TYPES, true)
            ? $input['alert_type']
            : 'maintenance_due';
        $severity = isset($input['severity']) && in_array($input['severity'], self::SEVERITIES, true)
            ? $input['severity']
            : 'warning';

        $entityType = isset($input['entity_type']) && $input['entity_type'] !== '' ? $input['entity_type'] : null;
        $entityId = $input['entity_id'] ?? null;

        return DB::transaction(function () use ($organizationId, $title, $description, $alertType, $severity, $entityType, $entityId): Alert {
            TenantContext::set($organizationId);

            return Alert::query()->create([
                'organization_id' => $organizationId,
                'alert_type' => $alertType,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'triggered_at' => now(),
                'status' => 'active',
                'notification_sent' => false,
                'escalation_level' => 0,
            ]);
        });
    }
}
