<?php

declare(strict_types=1);

use App\Actions\CreateOrganizationAction;
use App\Models\User;
use App\Services\TenantContext;

it('requires authentication for billing dashboard', function (): void {
    $response = $this->get(route('billing.index'));

    $response->assertRedirect(route('login'));
});

it('redirects to dashboard when user has no organization context', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    // Remove user from all orgs so defaultOrganization() is null (simulate no org)
    $user->organizations()->detach();
    TenantContext::forget();

    $response = $this->actingAs($user)->get(route('billing.index'));

    $response->assertRedirect(route('dashboard'));
});

it('shows billing dashboard for authenticated user with tenant', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $org = $user->defaultOrganization() ?? resolve(CreateOrganizationAction::class)->handle($user, 'Test Org');
    $user->switchOrganization($org);

    $response = $this->actingAs($user)->get(route('billing.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/index')
            ->has('organization')
            ->has('creditBalance')
            ->has('activePlan')
            ->has('isOnTrial')
            ->has('invoices')
        );
});
