<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\Alert;
use App\Models\Fleet\Driver;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class SendDriverReminderAction
{
    /**
     * Send a reminder to a driver by creating an alert tied to the driver (and optionally notifying them).
     *
     * @param  array{driver_id: int, title: string, description: string, severity?: string}  $input
     */
    public function handle(int $organizationId, int $userId, array $input): Alert
    {
        $driverId = (int) $input['driver_id'];
        $driver = Driver::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->find($driverId);

        throw_if($driver === null, InvalidArgumentException::class, "Driver {$driverId} not found or does not belong to this organization.");

        $title = mb_trim($input['title'] ?? '');
        $description = mb_trim($input['description'] ?? '');
        throw_if($title === '', InvalidArgumentException::class, 'Reminder title is required.');
        if ($description === '') {
            $description = $title;
        }

        $severity = isset($input['severity']) && in_array($input['severity'], ['info', 'warning', 'critical', 'emergency'], true)
            ? $input['severity']
            : 'info';

        return DB::transaction(function () use ($organizationId, $driverId, $title, $description, $severity): Alert {
            TenantContext::set($organizationId);

            return Alert::query()->create([
                'organization_id' => $organizationId,
                'alert_type' => 'working_time_violation',
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'entity_type' => 'driver',
                'entity_id' => $driverId,
                'triggered_at' => now(),
                'status' => 'active',
                'notification_sent' => false,
                'escalation_level' => 0,
            ]);
        });
    }
}
