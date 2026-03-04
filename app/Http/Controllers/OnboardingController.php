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
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $stepsCompleted = $user->onboarding_steps_completed ?? [];
        $initialStep = isset($stepsCompleted['current_step']) && is_int($stepsCompleted['current_step'])
            ? max(0, $stepsCompleted['current_step'])
            : 0;

        return Inertia::render('onboarding/show', [
            'status' => $request->session()->get('status'),
            'alreadyCompleted' => $user->onboarding_completed,
            'fleetOnlyApp' => config('app.fleet_only_app', false),
            'initialStep' => $initialStep,
        ]);
    }

    public function update(Request $request, #[CurrentUser] User $user): RedirectResponse
    {
        $validated = $request->validate([
            'current_step' => ['required', 'integer', 'min:0'],
        ]);

        $steps = $user->onboarding_steps_completed ?? [];
        $steps['current_step'] = $validated['current_step'];
        $user->onboarding_steps_completed = $steps;
        $user->save();

        return back();
    }

    public function store(#[CurrentUser] User $user, CompleteOnboardingAction $action): RedirectResponse
    {
        $action->handle($user);

        return to_route('dashboard')->with('status', __('Welcome! You’re all set.'));
    }
}
