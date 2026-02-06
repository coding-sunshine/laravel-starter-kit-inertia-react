<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ActivityType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

final class LogImpersonationEvents
{
    public function handleTakeImpersonation(TakeImpersonation $event): void
    {
        $this->log(
            $event->impersonator,
            $event->impersonated,
            ActivityType::ImpersonationStarted->value,
            [
                'impersonator_name' => $this->userName($event->impersonator),
                'impersonator_id' => $event->impersonator->getAuthIdentifier(),
                'impersonated_name' => $this->userName($event->impersonated),
                'impersonated_id' => $event->impersonated->getAuthIdentifier(),
            ]
        );
    }

    public function handleLeaveImpersonation(LeaveImpersonation $event): void
    {
        $this->log(
            $event->impersonator,
            $event->impersonated,
            ActivityType::ImpersonationEnded->value,
            [
                'impersonator_name' => $this->userName($event->impersonator),
                'impersonator_id' => $event->impersonator->getAuthIdentifier(),
                'impersonated_name' => $this->userName($event->impersonated),
                'impersonated_id' => $event->impersonated->getAuthIdentifier(),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Authenticatable $impersonator, Authenticatable $impersonated, string $description, array $properties): void
    {
        if (! $impersonator instanceof Model || ! $impersonated instanceof Model) {
            return;
        }

        activity()
            ->causedBy($impersonator)
            ->performedOn($impersonated)
            ->withProperties($properties)
            ->log($description);
    }

    private function userName(Authenticatable $user): string
    {
        return match (true) {
            isset($user->name) => (string) $user->name,
            isset($user->email) => (string) $user->email,
            default => (string) $user->getAuthIdentifier(),
        };
    }
}
