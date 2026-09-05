<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Services\Auth\HomeRedirectService;
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

        $homeRoute = resolve(HomeRedirectService::class)->getHomeRouteFor($user);

        return redirect()->intended(route($homeRoute));
    }
}
