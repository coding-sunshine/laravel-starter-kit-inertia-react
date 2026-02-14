<?php

declare(strict_types=1);

use App\Enums\VisibilityEnum;
use App\Models\Organization;
use App\Models\User;
use App\Models\VisibilityDemo;
use App\Services\TenantContext;

beforeEach(function (): void {
    $this->org1 = Organization::factory()->create();
    $this->org2 = Organization::factory()->create();
    $this->user1 = User::factory()->withoutTwoFactor()->create();
    $this->user2 = User::factory()->withoutTwoFactor()->create();
    $this->org1->addMember($this->user1, 'admin');
    $this->org2->addMember($this->user2, 'admin');
});

it('scopes visibility to current organization', function (): void {
    $this->actingAs($this->user1);
    TenantContext::set($this->org1);

    VisibilityDemo::query()->create(['title' => 'Org1 demo']);

    expect(VisibilityDemo::query()->count())->toBe(1);

    TenantContext::set($this->org2);
    expect(VisibilityDemo::query()->count())->toBe(0);

    $this->actingAs($this->user2);
    expect(VisibilityDemo::query()->count())->toBe(0);
});

it('allows shared item to be visible to target organization', function (): void {
    $this->actingAs($this->user1);
    TenantContext::set($this->org1);

    $demo = VisibilityDemo::query()->create(['title' => 'Shared demo']);
    $demo->shareWithOrganization($this->org2, 'view');

    expect($demo->fresh()->visibility)->toBe(VisibilityEnum::Shared);

    $this->actingAs($this->user2);
    TenantContext::set($this->org2);

    expect(VisibilityDemo::query()->count())->toBe(1);
    expect(VisibilityDemo::query()->first()->title)->toBe('Shared demo');
});

it('reverts to organization visibility when all shares are revoked', function (): void {
    $this->actingAs($this->user1);
    TenantContext::set($this->org1);

    $demo = VisibilityDemo::query()->create(['title' => 'Demo']);
    $demo->shareWithOrganization($this->org2, 'view');

    expect($demo->fresh()->visibility)->toBe(VisibilityEnum::Shared);

    $demo->revokeOrganizationShare($this->org2);

    expect($demo->fresh()->visibility)->toBe(VisibilityEnum::Organization);
});

it('clones item for organization (copy-on-write)', function (): void {
    $this->actingAs($this->user1);
    TenantContext::set($this->org1);

    $demo = VisibilityDemo::query()->create(['title' => 'Original']);
    $clone = $demo->cloneForOrganization($this->org2);

    expect($clone->organization_id)->toBe($this->org2->id);
    expect($clone->visibility)->toBe(VisibilityEnum::Organization);
    expect($clone->title)->toBe('Original');
    expect($clone->id)->not->toBe($demo->id);
});

it('canBeViewedBy and canBeEditedBy respect visibility and sharing', function (): void {
    $this->actingAs($this->user1);
    TenantContext::set($this->org1);

    $demo = VisibilityDemo::query()->create(['title' => 'Demo']);

    expect($demo->canBeViewedBy($this->user1))->toBeTrue();
    expect($demo->canBeEditedBy($this->user1))->toBeTrue();

    TenantContext::set($this->org2);
    expect($demo->canBeViewedBy($this->user2))->toBeFalse();
    expect($demo->canBeEditedBy($this->user2))->toBeFalse();

    TenantContext::set($this->org1);
    $demo->shareWithOrganization($this->org2, 'view');

    TenantContext::set($this->org2);
    expect($demo->canBeViewedBy($this->user2))->toBeTrue();
    expect($demo->canBeEditedBy($this->user2))->toBeFalse();

    TenantContext::set($this->org1);
    $demo->revokeOrganizationShare($this->org2);
    $demo->shareWithOrganization($this->org2, 'edit');

    TenantContext::set($this->org2);
    expect($demo->canBeViewedBy($this->user2))->toBeTrue();
    expect($demo->canBeEditedBy($this->user2))->toBeTrue();
});
