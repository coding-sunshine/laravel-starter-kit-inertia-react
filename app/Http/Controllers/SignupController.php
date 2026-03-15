<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ProvisionSubscriberFeaturesAction;
use App\Billing\Contracts\SubscriptionBillingContract;
use App\Billing\Drivers\EwayBillingDriver;
use App\Billing\Drivers\StripeBillingDriver;
use App\Enums\OnboardingStep;
use App\Events\SubscriberSignedUpEvent;
use App\Http\Requests\SignupRequest;
use App\Models\Billing\Plan;
use App\Models\OnboardingProgress;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final readonly class SignupController
{
    public function __construct(
        private ProvisionSubscriberFeaturesAction $provisionFeatures,
    ) {}

    /**
     * Show the signup landing page (plan selection step).
     */
    public function index(): Response
    {
        $plans = Plan::query()
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'slug' => $plan->slug,
                'name' => is_array($plan->name) ? ($plan->name['en'] ?? $plan->slug) : $plan->name,
                'description' => is_array($plan->description) ? ($plan->description['en'] ?? '') : ($plan->description ?? ''),
                'price' => $plan->price,
                'setup_fee' => $plan->setup_fee ?? 0,
                'currency' => mb_strtoupper($plan->currency),
                'interval' => $plan->invoice_interval,
                'features' => $plan->features ?? [],
                'max_users' => $plan->max_users,
                'ai_credits' => $plan->ai_credits_per_period ?? 0,
            ]);

        return Inertia::render('signup/index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show the registration form for a chosen plan.
     */
    public function create(Request $request): Response
    {
        $planSlug = $request->query('plan', 'fusion-starter');
        $plan = Plan::query()->where('slug', $planSlug)->first();

        return Inertia::render('signup/register', [
            'planSlug' => $planSlug,
            'planName' => $plan ? (is_array($plan->name) ? ($plan->name['en'] ?? $plan->slug) : $plan->name) : $planSlug,
            'planPrice' => $plan?->price ?? 0,
            'setupFee' => $plan?->setup_fee ?? 0,
            'gateway' => config('billing.billing_gateway', 'stripe'),
        ]);
    }

    /**
     * Provision the subscriber: create user → org → subscription → features → onboarding.
     * The subscriber never sees a "Create Organisation" form.
     */
    public function provision(SignupRequest $request): RedirectResponse|Response
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($data): array {
                // 1. Create user
                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make(Str::random(32)), // temp password
                    'created_via' => 'signup',
                    'email_verified_at' => null,
                ]);

                // 2. Assign subscriber role (org_id = 0 for global scope)
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => DB::table('roles')->where('name', 'subscriber')->where('guard_name', 'web')->value('id') ?? 0,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'organization_id' => 0,
                ]);

                // 3. Auto-create one organization for this subscriber
                $orgName = ! empty($data['business_name']) ? $data['business_name'] : "{$data['name']}'s Org";
                $org = Organization::query()->firstOrCreate(
                    ['owner_id' => $user->id],
                    ['name' => $orgName]
                );

                // 4. Add user to org with is_default = true
                DB::table('organization_user')->insertOrIgnore([
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                    'is_default' => true,
                    'joined_at' => now(),
                    'invited_by' => null,
                ]);

                // 5. Subscribe org to plan
                $plan = Plan::query()->where('slug', $data['plan_slug'])->firstOrFail();
                try {
                    $org->subscribeTo($plan);
                } catch (Throwable $e) {
                    Log::warning('signup.subscribe_error', ['error' => $e->getMessage()]);
                }

                // 6. Provision features from plan
                $this->provisionFeatures->handle($user, $org, $plan);

                // 7. Initialize onboarding checklist rows
                $now = Carbon::now();
                foreach (OnboardingStep::cases() as $step) {
                    OnboardingProgress::query()->firstOrCreate(
                        ['user_id' => $user->id, 'step_key' => $step->value],
                        ['created_at' => $now]
                    );
                }

                return ['user' => $user, 'org' => $org, 'plan' => $plan];
            });

            /** @var User $user */
            $user = $result['user'];
            /** @var Organization $org */
            $org = $result['org'];

            // 8. Dispatch billing checkout (outside transaction — can fail gracefully)
            $billingResult = $this->billingDriver()->checkout(
                $user,
                $org,
                $data['plan_slug'],
                route('signup.complete', ['user' => $user->id]),
                route('signup.index')
            );

            // 9. Fire welcome event
            SubscriberSignedUpEvent::dispatch($user, $org, $data['plan_slug']);

            // 10. Log in the user
            Auth::login($user);

            // If billing driver returned a stub redirect (no real gateway), skip to completion
            if (str_contains((string) $billingResult['redirect_url'], 'stub=1')) {
                return redirect()->route('signup.complete', ['user' => $user->id]);
            }

            return Inertia::location($billingResult['redirect_url']);
        } catch (Throwable $e) {
            Log::error('signup.provision_error', ['error' => $e->getMessage()]);

            return back()->withErrors(['email' => 'Signup failed. Please try again or contact support.']);
        }
    }

    /**
     * Signup completion — send user to onboarding checklist.
     */
    public function complete(Request $request): RedirectResponse
    {
        // Mark password step as pending (user should reset temp password)
        return redirect()->route('signup.onboarding');
    }

    /**
     * Show guided onboarding checklist.
     */
    public function onboarding(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $progress = OnboardingProgress::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (OnboardingProgress $p) => $p->step_key->value);

        $steps = collect(OnboardingStep::cases())->map(function (OnboardingStep $step) use ($progress) {
            $record = $progress->get($step->value);

            return [
                'key' => $step->value,
                'label' => $step->label(),
                'description' => $step->description(),
                'completed' => $record && $record->completed_at !== null,
                'completed_at' => $record?->completed_at?->toISOString(),
            ];
        });

        $completedCount = $steps->where('completed', true)->count();

        return Inertia::render('signup/onboarding', [
            'steps' => $steps->values(),
            'completedCount' => $completedCount,
            'totalSteps' => $steps->count(),
        ]);
    }

    /**
     * Mark an onboarding step as complete.
     */
    public function completeStep(Request $request, string $stepKey): RedirectResponse
    {
        $request->validate(['step_key' => ['sometimes', 'string']]);

        /** @var User $user */
        $user = $request->user();

        OnboardingProgress::query()->updateOrCreate(
            ['user_id' => $user->id, 'step_key' => $stepKey],
            ['completed_at' => Carbon::now()]
        );

        return back();
    }

    private function billingDriver(): SubscriptionBillingContract
    {
        $gateway = config('billing.billing_gateway', config('billing.default_gateway', 'stripe'));

        return match ($gateway) {
            'eway' => new EwayBillingDriver,
            default => new StripeBillingDriver,
        };
    }
}
