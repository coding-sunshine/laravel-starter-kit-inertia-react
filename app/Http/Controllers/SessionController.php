<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateSessionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SessionController
{
    public function create(Request $request): Response
    {
        return Inertia::render('session/create', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(CreateSessionRequest $request): RedirectResponse
    {
        $user = $request->validateCredentials();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return to_route('two-factor.login');
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $authenticatedUser = $request->user();
        // dd($authenticatedUser->roles);
        if ($authenticatedUser !== null) {
            if ($authenticatedUser->hasRole('super-admin')) {
                return redirect()->intended(route('dashboard', absolute: false));
            }

            if ($authenticatedUser->hasRole('admin')) {
                return redirect()->intended(route('rakes.index', absolute: false));
            }

            if ($authenticatedUser->hasRole('dispatch-manage-admin')) {
                return redirect()->intended(route('vehicle-dispatch.index', absolute: false));
            }

            if ($authenticatedUser->hasRole('user')) {
                return redirect()->intended(route('road-dispatch.daily-vehicle-entries.index', absolute: false));
            }

            if ($authenticatedUser->hasRole('viewer')) {
                return redirect()->intended(route('dashboard', absolute: false));
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
