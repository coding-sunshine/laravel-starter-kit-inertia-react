<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use SensitiveParameter;
use Spatie\Permission\Models\Role;

final readonly class CreateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): User
    {
        $user = User::query()->create([
            ...$attributes,
            'password' => $password,
        ]);

        $defaultRole = config('permission.default_role');
        if (is_string($defaultRole) && $defaultRole !== '' && Role::query()->where('name', $defaultRole)->exists()) {
            $user->assignRole($defaultRole);
        }

        event(new Registered($user));

        return $user;
    }
}
