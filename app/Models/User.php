<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /**
     * @use HasFactory<UserFactory>
     */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly(['name', 'email', 'email_verified_at', 'two_factor_confirmed_at']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can('access admin panel');
    }

    /**
     * Create a personal access token with abilities derived from the user's permissions.
     * Users with bypass-permissions get ['*']; others get their permission names.
     */
    public function createTokenWithPermissionAbilities(string $name, ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        $abilities = $this->can('bypass-permissions')
            ? ['*']
            : $this->getAllPermissions()->pluck('name')->all();

        return $this->createToken($name, $abilities, $expiresAt);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'remember_token' => 'string',
            'two_factor_secret' => 'string',
            'two_factor_recovery_codes' => 'string',
            'two_factor_confirmed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Whether this user is the only one with the super-admin role (cannot remove or delete).
     */
    public function isLastSuperAdmin(): bool
    {
        $superAdminRole = \Spatie\Permission\Models\Role::query()
            ->where('name', 'super-admin')
            ->first();

        if ($superAdminRole === null) {
            return false;
        }

        $userIdsWithSuperAdmin = $superAdminRole->users()->pluck('id')->all();

        return count($userIdsWithSuperAdmin) === 1 && in_array($this->getKey(), $userIdsWithSuperAdmin, true);
    }
}
