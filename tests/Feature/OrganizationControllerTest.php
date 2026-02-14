<?php

declare(strict_types=1);

use App\Actions\CreateOrganizationAction;
use App\Models\Organization;
use App\Models\User;

it('requires authentication for organizations index', function (): void {
    $response = $this->get(route('organizations.index'));

    $response->assertRedirect(route('login'));
});

it('shows organizations index for authenticated user', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get(route('organizations.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('organizations/index')
            ->has('organizations')
            ->has('currentOrganization')
        );
});

it('allows creating an organization when config allows', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->post(route('organizations.store'), [
        'name' => 'Acme Inc.',
    ]);

    $response->assertRedirect();

    $org = Organization::query()->where('name', 'Acme Inc.')->first();
    expect($org)->not->toBeNull()
        ->and($org->owner_id)->toBe($user->id);
});

it('shows organization for member', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $org = $user->defaultOrganization() ?? resolve(CreateOrganizationAction::class)->handle($user, 'Test Org');

    $response = $this->actingAs($user)->get(route('organizations.show', $org));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('organizations/show')
            ->has('organization')
            ->where('organization.id', $org->id)
        );
});

it('denies showing organization to non-member', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $otherOrg = Organization::factory()->create();

    $response = $this->actingAs($user)->get(route('organizations.show', $otherOrg));

    $response->assertForbidden();
});
