<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Actions\Fleet\SendDriverReminderAction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class SendDriverReminder implements Tool
{
    public function __construct(
        private int $organizationId,
        private int $userId,
    ) {}

    public function description(): string
    {
        return 'Send a reminder to a driver (creates an alert tied to the driver). Use when the user asks to remind a driver about compliance, licence renewal, training, or any task. Requires driver_id, title, and description. Optional: severity (info, warning, critical, emergency).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'driver_id' => $schema->integer()->description('Driver ID (must belong to current organization)')->required(),
            'title' => $schema->string()->description('Short title for the reminder')->required(),
            'description' => $schema->string()->description('Full reminder message')->required(),
            'severity' => $schema->string()->description('One of: info, warning, critical, emergency'),
        ];
    }

    public function handle(Request $request): string
    {
        $input = [
            'driver_id' => (int) $request['driver_id'],
            'title' => (string) $request['title'],
            'description' => (string) $request['description'],
            'severity' => isset($request['severity']) && $request['severity'] !== '' ? (string) $request['severity'] : null,
        ];

        try {
            $alert = resolve(SendDriverReminderAction::class)->handle(
                $this->organizationId,
                $this->userId,
                $input,
            );
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        $url = url("/fleet/alerts/{$alert->id}");

        return "Reminder sent to driver: alert #{$alert->id} – {$alert->title}. View it here: {$url}";
    }
}
