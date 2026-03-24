<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Features\ImpersonationFeature;
use App\Filament\Resources\Users\UserResource;
use App\Services\ActivityLogRbac;
use App\Support\FeatureHelper;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
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
    public function mutateFormDataBeforeFill(array $data): array
    {
        $roleTeamKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $data['roles'] = $this->getRecord()
            ->roles()
            ->wherePivot($roleTeamKey, 0)
            ->value('id');

        $usesSectionAssignments = $this->roleUsesSectionAssignments($data['roles'] !== null ? (int) $data['roles'] : null);
        if ($usesSectionAssignments) {
            $data['siding_ids_multi'] = DB::table('user_siding')
                ->where('user_id', $this->getRecord()->getKey())
                ->orderByDesc('is_primary')
                ->orderBy('siding_id')
                ->pluck('siding_id')
                ->all();
            $data['siding_shifts_multi'] = $this->getRecord()->sidingShifts()->pluck('siding_shifts.id')->all();
            $data['siding_id_single'] = null;
        } else {
            $data['siding_id_single'] = $this->getRecord()->siding_id;
            $data['siding_ids_multi'] = [];
            $data['siding_shifts_multi'] = [];
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('updatePassword')
                ->label('Update Password')
                ->icon('heroicon-o-key')
                ->visible(fn (): bool => auth()->user()?->hasRole('super-admin') === true)
                ->form([
                    TextInput::make('new_password')
                        ->label('New password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->minLength(8)
                        ->same('new_password_confirmation'),
                    TextInput::make('new_password_confirmation')
                        ->label('Confirm new password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('new_password'),
                ])
                ->action(function (array $data): void {
                    $this->record->forceFill([
                        'password' => $data['new_password'],
                    ])->save();

                    Notification::make()
                        ->title('Password updated successfully.')
                        ->success()
                        ->send();
                }),
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
        $this->pendingRoleId = isset($data['roles']) && $data['roles'] !== ''
            ? (int) $data['roles']
            : null;
        $this->pendingRoleName = $this->pendingRoleId !== null
            ? Role::query()->whereKey($this->pendingRoleId)->value('name')
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

        $user = $this->getRecord();
        if (! $user->isLastSuperAdmin() || ! $user->hasRole('super-admin')) {
            return $data;
        }

        $superAdminRole = Role::query()->where('name', 'super-admin')->first();
        if ($superAdminRole === null) {
            return $data;
        }

        $hasSuperAdmin = $this->pendingRoleId === (int) $superAdminRole->getKey();
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
        if (! $this->usesSectionAssignments) {
            $this->record->forceFill(['siding_id' => $this->pendingSidingId])->save();
            DB::table('user_siding')->where('user_id', $this->record->getKey())->delete();
            $this->record->sidingShifts()->sync([]);
        }

        if ($this->usesSectionAssignments) {
            $this->record->forceFill(['siding_id' => null])->save();

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
            $this->record->syncRoles([$this->pendingRoleId], 0);
        }
        $this->record->load('roles', 'sidings');
        resolve(ActivityLogRbac::class)->logRolesUpdated(
            $this->record,
            $this->previousRoleNames,
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
