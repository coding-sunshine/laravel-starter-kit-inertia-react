<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Features\ImpersonationFeature;
use App\Filament\Resources\Users\UserResource;
use App\Services\ActivityLogRbac;
use App\Support\FeatureHelper;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use STS\FilamentImpersonate\Actions\Impersonate;

final class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @var array<int, string>
     */
    private array $previousRoleNames = [];

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
    public function mutateFormDataBeforeFill(array $data): array
    {
        $data['tag_names'] = $this->getRecord()->tags->pluck('name')->values()->all();
        $data['sidings'] = $this->getRecord()->sidings->pluck('id')->values()->all();
        $primary = $this->getRecord()->sidings()->wherePivot('is_primary', true)->first();
        $data['primary_siding_id'] = $primary?->getKey();
        $data['siding_shifts'] = $this->getRecord()->sidingShifts->pluck('id')->values()->all();

        $roleTeamKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $data['roles'] = $this->getRecord()
            ->roles()
            ->wherePivot($roleTeamKey, 0)
            ->pluck('id')
            ->values()
            ->all();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Impersonate::make()
                ->record($this->getRecord())
                ->visible(fn (): bool => auth()->user()?->hasRole('super-admin') === true
                    && FeatureHelper::isActiveForClass(ImpersonationFeature::class, auth()->user())),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingTagNames = array_values(array_filter(
            is_array($data['tag_names'] ?? null) ? $data['tag_names'] : [],
            fn ($v): bool => is_string($v) && $v !== ''
        ));
        unset($data['tag_names']);

        $sidingIds = array_filter(array_map(intval(...), (array) ($data['sidings'] ?? [])));
        $primaryId = isset($data['primary_siding_id']) ? (int) $data['primary_siding_id'] : null;
        $this->pendingSidingsPivot = [];
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
        unset($data['siding_shifts']);

        $roles = (array) ($data['roles'] ?? []);
        $this->pendingRoleIds = array_values(array_filter(array_map(intval(...), $roles)));
        unset($data['roles']);

        $user = $this->getRecord();
        if (! $user->isLastSuperAdmin() || ! $user->hasRole('super-admin')) {
            return $data;
        }

        $superAdminRole = Role::query()->where('name', 'super-admin')->first();
        if ($superAdminRole === null) {
            return $data;
        }

        $hasSuperAdmin = in_array($superAdminRole->getKey(), $this->pendingRoleIds, true);
        if (! $hasSuperAdmin) {
            throw ValidationException::withMessages([
                'roles' => ['Cannot remove the super-admin role from the last super-admin user.'],
            ]);
        }

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->previousRoleNames = ActivityLogRbac::roleNamesFrom($this->record);
    }

    protected function afterSave(): void
    {
        $this->record->syncTags($this->pendingTagNames);
        $this->record->sidings()->sync($this->pendingSidingsPivot);
        if ($this->pendingSidingShiftsPivot !== []) {
            $this->record->sidingShifts()->sync($this->pendingSidingShiftsPivot);
        }
        if ($this->pendingRoleIds !== []) {
            $this->record->syncRoles($this->pendingRoleIds, 0);
        }
        $this->record->load('roles', 'sidings');
        resolve(ActivityLogRbac::class)->logRolesUpdated(
            $this->record,
            $this->previousRoleNames,
            ActivityLogRbac::roleNamesFrom($this->record)
        );
    }
}
