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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function mutateFormDataBeforeFill(array $data): array
    {
        $primary = DB::table('user_siding')
            ->where('user_id', $this->getRecord()->getKey())
            ->where('is_primary', true)
            ->first();
        $data['siding_id'] = $this->getRecord()->siding_id ?? $primary?->siding_id;
        $data['siding_shifts'] = $this->getRecord()->sidingShifts()->value('siding_shifts.id');

        $roleTeamKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $data['roles'] = $this->getRecord()
            ->roles()
            ->wherePivot($roleTeamKey, 0)
            ->value('id');

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
        unset($data['siding_shifts']);

        $this->pendingRoleId = isset($data['roles']) && $data['roles'] !== ''
            ? (int) $data['roles']
            : null;
        $this->pendingRoleName = $this->pendingRoleId !== null
            ? Role::query()->whereKey($this->pendingRoleId)->value('name')
            : null;
        unset($data['roles']);

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
        if ($this->pendingRoleName === 'admin') {
            $this->record->forceFill(['siding_id' => $this->pendingSidingId])->save();
            DB::table('user_siding')->where('user_id', $this->record->getKey())->delete();
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
            $this->record->syncRoles([$this->pendingRoleId], 0);
        }
        $this->record->load('roles', 'sidings');
        resolve(ActivityLogRbac::class)->logRolesUpdated(
            $this->record,
            $this->previousRoleNames,
            ActivityLogRbac::roleNamesFrom($this->record)
        );
    }
}
