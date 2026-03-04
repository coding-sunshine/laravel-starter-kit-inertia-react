<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Actions\Fleet\CreateAlertFromPromptAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class CreateAlert implements Tool
{
    public function __construct(
        private int $organizationId,
        private int $userId,
    ) {}

    public function description(): string
    {
        return 'Create a fleet alert (e.g. maintenance due, compliance, custom). Use when the user asks to create or add an alert. Requires title and description. Optional: alert_type, severity, entity_type (vehicle, driver, etc.), entity_id. Returns a confirmation with alert id.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Short title for the alert')->required(),
            'description' => $schema->string()->description('Description of the alert')->required(),
            'alert_type' => $schema->string()->description('One of: compliance_expiry, maintenance_due, defect_reported, incident_reported, behavior_violation, fuel_anomaly, cost_threshold, geofence_violation, speed_violation, working_time_violation, system_error'),
            'severity' => $schema->string()->description('One of: info, warning, critical, emergency'),
            'entity_type' => $schema->string()->description('Optional: vehicle, driver, etc.'),
            'entity_id' => $schema->integer()->description('Optional: ID of the entity'),
        ];
    }

    public function handle(Request $request): string
    {
        $input = [
            'title' => (string) $request['title'],
            'description' => (string) $request['description'],
            'alert_type' => isset($request['alert_type']) && $request['alert_type'] !== '' ? (string) $request['alert_type'] : null,
            'severity' => isset($request['severity']) && $request['severity'] !== '' ? (string) $request['severity'] : null,
            'entity_type' => isset($request['entity_type']) && $request['entity_type'] !== '' ? (string) $request['entity_type'] : null,
            'entity_id' => isset($request['entity_id']) ? (int) $request['entity_id'] : null,
        ];

        try {
            $alert = resolve(CreateAlertFromPromptAction::class)->handle(
                $this->organizationId,
                $this->userId,
                $input,
            );
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        $url = url("/fleet/alerts/{$alert->id}");

        return "Alert created: #{$alert->id} – {$alert->title}. View it here: {$url}";
    }
}
