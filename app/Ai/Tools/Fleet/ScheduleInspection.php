<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Actions\Fleet\ScheduleInspectionFromPromptAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ScheduleInspection implements Tool
{
    public function __construct(
        private int $organizationId,
        private int $userId,
    ) {}

    public function description(): string
    {
        return 'Schedule a vehicle inspection (DVIR or check). Use when the user asks to schedule or add an inspection. Requires vehicle_id. Optional: scheduled_date (Y-m-d), template_id (vehicle check template ID). Returns a link to the inspection.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'vehicle_id' => $schema->integer()->description('Vehicle ID (must belong to current organization)')->required(),
            'scheduled_date' => $schema->string()->description('Optional date (Y-m-d) for the inspection'),
            'template_id' => $schema->integer()->description('Optional vehicle check template ID; if omitted, a default inspection template is used'),
        ];
    }

    public function handle(Request $request): string
    {
        $input = [
            'vehicle_id' => (int) $request['vehicle_id'],
            'scheduled_date' => isset($request['scheduled_date']) && $request['scheduled_date'] !== '' ? (string) $request['scheduled_date'] : null,
            'template_id' => isset($request['template_id']) ? (int) $request['template_id'] : null,
        ];

        try {
            $check = resolve(ScheduleInspectionFromPromptAction::class)->handle(
                $this->organizationId,
                $this->userId,
                $input,
            );
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        $url = url("/fleet/vehicles/{$check->vehicle_id}#checks");

        return "Inspection scheduled: #{$check->id} for vehicle on {$check->check_date->format('Y-m-d')}. View it here: {$url}";
    }
}
