<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\ActivityLogRbac;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $pendingSidingShiftsPivot = [];

    private ?int $pendingRoleId = null;

    private ?int $pendingSidingId = null;

    private ?string $pendingRoleName = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingSidingId = isset($data['siding_id']) ? (int) $data['siding_id'] : null;
        unset($data['siding_id']);

        $this->pendingSidingShiftsPivot = [];
        $shiftId = isset($data['siding_shifts']) && $data['siding_shifts'] !== ''
            ? (int) $data['siding_shifts']
            : null;
        if ($shiftId !== null) {
            $this->pendingSidingShiftsPivot[$shiftId] = [
                'assigned_at' => now(),
                'is_active' => true,
            ];
        }
        unset($data['siding_shifts'], $data['password_confirmation']);

        $roleId = isset($data['roles']) && $data['roles'] !== ''
            ? (int) $data['roles']
            : null;

        if ($roleId === null) {
            $defaultRoleName = config('permission.default_role');
            if (is_string($defaultRoleName) && $defaultRoleName !== '') {
                $defaultRole = Role::query()->where('name', $defaultRoleName)->first();
                if ($defaultRole !== null) {
                    $roleId = (int) $defaultRole->getKey();
                }
            }
        }

        $this->pendingRoleId = $roleId;
        $this->pendingRoleName = $roleId !== null
            ? Role::query()->whereKey($roleId)->value('name')
            : null;
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

        if ($this->pendingRoleName === 'admin') {
            $this->record->forceFill(['siding_id' => $this->pendingSidingId])->save();
            $this->record->sidingShifts()->sync([]);
        }

        if (in_array($this->pendingRoleName, ['user', 'empty-weighment-shift'], true) && $this->pendingSidingId !== null) {
            $this->record->forceFill(['siding_id' => null])->save();

            DB::table('user_siding')->updateOrInsert(
                [
                    'user_id' => $this->record->getKey(),
                    'siding_id' => $this->pendingSidingId,
                ],
                [
                    'is_primary' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('user_siding')
                ->where('user_id', $this->record->getKey())
                ->where('siding_id', '!=', $this->pendingSidingId)
                ->delete();
        }

        if (in_array($this->pendingRoleName, ['user', 'empty-weighment-shift'], true) && $this->pendingSidingShiftsPivot !== []) {
            $this->record->sidingShifts()->sync($this->pendingSidingShiftsPivot);
        } elseif (! in_array($this->pendingRoleName, ['user', 'empty-weighment-shift'], true)) {
            $this->record->sidingShifts()->sync([]);
        }

        if ($this->pendingRoleId !== null) {
            // Assign roles for the global team (organization_id = 0).
            $this->record->syncRoles([$this->pendingRoleId], 0);
        }
        $this->record->load('roles', 'sidings');
        resolve(ActivityLogRbac::class)->logRolesAssigned(
            $this->record,
            ActivityLogRbac::roleNamesFrom($this->record)
        );
    }
}
