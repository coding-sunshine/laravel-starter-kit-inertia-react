<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

final class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($user === null) {
            return redirect()->intended(config('fortify.home'));
        }

        if ($user->hasRole('super-admin')) {
            return redirect()->intended('/dashboard');
        }

        if ($user->hasRole('admin')) {
            return redirect()->intended('/rakes');
        }

        if ($user->hasRole('dispatch-manage-admin')) {
            return redirect()->intended('/vehicle-dispatch');
        }

        if ($user->hasRole('user')) {
            return redirect()->intended('/road-dispatch/daily-vehicle-entries');
        }

        if ($user->hasRole('viewer')) {
            return redirect()->intended('/dashboard');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
