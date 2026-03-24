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
     * @var list<int>
     */
    private array $pendingSidingIds = [];

    /**
     * @var list<int>
     */
    private array $pendingShiftIds = [];

    private bool $usesSectionAssignments = false;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['password_confirmation']);

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
        $this->usesSectionAssignments = $this->roleUsesSectionAssignments($this->pendingRoleId);
        unset($data['roles']);

        $this->pendingSidingShiftsPivot = [];
        $this->pendingSidingIds = [];
        $this->pendingShiftIds = [];

        if ($this->usesSectionAssignments) {
            $this->pendingSidingIds = array_values(array_filter(array_map(
                intval(...),
                (array) ($data['siding_ids_multi'] ?? [])
            )));
            $this->pendingShiftIds = array_values(array_filter(array_map(
                intval(...),
                (array) ($data['siding_shifts_multi'] ?? [])
            )));
            foreach ($this->pendingShiftIds as $shiftId) {
                $this->pendingSidingShiftsPivot[$shiftId] = [
                    'assigned_at' => now(),
                    'is_active' => true,
                ];
            }
            $this->pendingSidingId = null;
        } else {
            $this->pendingSidingId = isset($data['siding_id_single']) && $data['siding_id_single'] !== ''
                ? (int) $data['siding_id_single']
                : null;
        }
        unset($data['siding_ids_multi'], $data['siding_id_single'], $data['siding_shifts_multi']);

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
        $this->record->forceFill([
            'siding_id' => $this->usesSectionAssignments ? null : $this->pendingSidingId,
        ])->save();

        if (! $this->usesSectionAssignments) {
            DB::table('user_siding')->where('user_id', $this->record->getKey())->delete();
            if ($this->pendingSidingId !== null) {
                DB::table('user_siding')->insert([
                    'user_id' => $this->record->getKey(),
                    'siding_id' => $this->pendingSidingId,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->record->sidingShifts()->sync([]);
        }

        if ($this->usesSectionAssignments) {
            DB::table('user_siding')->where('user_id', $this->record->getKey())->delete();
            foreach ($this->pendingSidingIds as $index => $sidingId) {
                DB::table('user_siding')->insert([
                    'user_id' => $this->record->getKey(),
                    'siding_id' => $sidingId,
                    'is_primary' => $index === 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($this->usesSectionAssignments) {
            $this->record->sidingShifts()->sync($this->pendingSidingShiftsPivot);
        } else {
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

    private function roleUsesSectionAssignments(?int $roleId): bool
    {
        if ($roleId === null) {
            return false;
        }

        return Role::query()
            ->whereKey($roleId)
            ->where(function ($query): void {
                $query
                    ->whereHas('permissions', fn ($q) => $q->where('name', 'like', 'sections.railway_siding_record_data.%'))
                    ->orWhereHas('permissions', fn ($q) => $q->where('name', 'like', 'sections.railway_siding_empty_weighment.%'));
            })
            ->exists();
    }
}
