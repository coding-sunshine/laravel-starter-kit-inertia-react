<?php

declare(strict_types=1);

namespace App\Models;

use App\Features\ImpersonationFeature;
use App\Models\Concerns\Categorizable;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate as ImpersonateTrait;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Pennant\Feature;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Spatie\PersonalDataExport\ExportsPersonalData;
use Spatie\PersonalDataExport\PersonalDataSelection;
use Spatie\Tags\HasTags;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read string|null $avatar
 * @property-read string|null $avatar_profile
 * @property-read CarbonInterface|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonInterface|null $two_factor_confirmed_at
 * @property bool $onboarding_completed
 * @property array<string>|null $onboarding_steps_completed
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class User extends Authenticatable implements ExportsPersonalData, FilamentUser, HasMedia, MustVerifyEmail
{
    /**
     * @use HasFactory<UserFactory>
     */
    use Categorizable, HasApiTokens, HasFactory, HasRoles, HasTags, ImpersonateTrait, InteractsWithMedia, LogsActivity, Notifiable, Searchable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $appends = [
        'avatar',
        'avatar_profile',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the indexable data array for the model (Typesense).
     * Only safe, searchable fields; id and created_at must be string and int64.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 48, 48)
            ->nonQueued();

        $this->addMediaConversion('profile')
            ->performOnCollections('avatar')
            ->fit(Fit::Crop, 192, 192)
            ->nonQueued();
    }

    /**
     * Avatar URL (thumb conversion) for nav/header, or null when no avatar.
     */
    public function getAvatarAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('avatar', 'thumb');

        return $url !== '' ? $url : null;
    }

    /**
     * Avatar URL (profile conversion) for profile/settings preview, or null when no avatar.
     */
    public function getAvatarProfileAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('avatar', 'profile');

        return $url !== '' ? $url : null;
    }

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
            'onboarding_completed' => 'boolean',
            'onboarding_steps_completed' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Only super-admins may impersonate, and only when Impersonation feature is active.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('super-admin')
            && Feature::for($this)->active(ImpersonationFeature::class);
    }

    /**
     * Super-admins cannot be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->hasRole('super-admin');
    }

    /**
     * Whether this user is the only one with the super-admin role (cannot remove or delete).
     */
    public function selectPersonalData(PersonalDataSelection $personalDataSelection): void
    {
        $personalDataSelection->add('user.json', [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ]);
    }

    public function personalDataExportName(): string
    {
        return 'personal-data-'.Str::slug($this->name).'.zip';
    }

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
