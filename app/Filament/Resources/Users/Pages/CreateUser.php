<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\ActivityLogRbac;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @var list<string>
     */
    private array $pendingTagNames = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $pendingSidingsPivot = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $pendingSidingShiftsPivot = [];

    /**
     * @var list<int>
     */
    private array $pendingRoleIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingTagNames = array_values(array_filter(
            is_array($data['tag_names'] ?? null) ? $data['tag_names'] : [],
            fn ($v): bool => is_string($v) && $v !== ''
        ));
        unset($data['tag_names']);

        $sidingIds = array_filter(array_map(intval(...), (array) ($data['sidings'] ?? [])));
        $primaryId = isset($data['primary_siding_id']) ? (int) $data['primary_siding_id'] : null;
        foreach ($sidingIds as $id) {
            $this->pendingSidingsPivot[$id] = [
                'is_primary' => $primaryId === $id,
                'assigned_at' => now(),
            ];
        }
        unset($data['sidings'], $data['primary_siding_id']);

        $sidingShiftIds = array_filter(array_map(intval(...), (array) ($data['siding_shifts'] ?? [])));
        foreach ($sidingShiftIds as $shiftId) {
            $this->pendingSidingShiftsPivot[$shiftId] = [
                'assigned_at' => now(),
                'is_active' => true,
            ];
        }
        unset($data['siding_shifts'], $data['password_confirmation']);

        $roles = (array) ($data['roles'] ?? []);
        if ($roles === [] || $roles === null) {
            $defaultRoleName = config('permission.default_role');
            if (is_string($defaultRoleName) && $defaultRoleName !== '') {
                $defaultRole = Role::query()->where('name', $defaultRoleName)->first();
                if ($defaultRole !== null) {
                    $roles = [$defaultRole->getKey()];
                }
            }
        }

        $this->pendingRoleIds = array_values(array_filter(array_map(intval(...), $roles)));
        unset($data['roles']);

        // Users created by superadmin in Filament should be immediately active / verified.
        if (! array_key_exists('email_verified_at', $data) || $data['email_verified_at'] === null) {
            $data['email_verified_at'] = now();
        }

        // Treat admin-created users as already onboarded to avoid onboarding loops.
        if (! array_key_exists('onboarding_completed', $data)) {
            $data['onboarding_completed'] = true;
        }

        return $data;
    }

    protected function afterCreate(): void
    {

        $this->record->syncTags($this->pendingTagNames);
        $this->record->sidings()->sync($this->pendingSidingsPivot);

        if ($this->pendingSidingShiftsPivot !== []) {
            $this->record->sidingShifts()->sync($this->pendingSidingShiftsPivot);
        }
        if ($this->pendingRoleIds !== []) {
            // Assign roles for the global team (organization_id = 0).
            $this->record->syncRoles($this->pendingRoleIds, 0);
        }
        $this->record->load('roles', 'sidings');
        resolve(ActivityLogRbac::class)->logRolesAssigned(
            $this->record,
            ActivityLogRbac::roleNamesFrom($this->record)
        );
    }
}
