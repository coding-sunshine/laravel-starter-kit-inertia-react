<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CompleteOnboardingAction;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OnboardingController
{
    public function show(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->onboarding_completed) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('onboarding/show', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(#[CurrentUser] User $user, CompleteOnboardingAction $action): RedirectResponse
    {
        $action->handle($user);

        return redirect()->route('dashboard')->with('status', __('Welcome! You’re all set.'));
    }
}
