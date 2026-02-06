<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Http\Request;

final readonly class UpdateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes, ?Request $request = null): void
    {
        $emailChanged = array_key_exists('email', $attributes) && $user->email !== $attributes['email'];

        $fillable = array_diff_key($attributes, array_flip(['avatar']));
        $user->update([
            ...$fillable,
            ...($emailChanged ? ['email_verified_at' => null] : []),
        ]);

        if ($request?->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('avatar')
                ->toMediaCollection('avatar');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
