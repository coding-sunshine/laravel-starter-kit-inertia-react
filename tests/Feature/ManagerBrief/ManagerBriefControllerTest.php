<?php

declare(strict_types=1);

use App\DataTransferObjects\ManagerBrief\ActionCard;
use App\DataTransferObjects\ManagerBrief\Payload;
use App\Models\Organization;
use App\Models\Siding;
use App\Models\User;
use App\Services\ManagerBrief\TrendStrip;
use App\Services\SidingContext;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\Essential\ManagerBriefPermissionsSeeder;
use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

// Clear static context between every test so permission checks are not polluted
// by TenantContext or Spatie team-ID state left by preceding tests in the suite.
afterEach(function (): void {
    SidingContext::flush();
    TenantContext::forget();
    resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(null);
});

// ---------------------------------------------------------------------------
// 16.1 — 403 without view permission
// ---------------------------------------------------------------------------

test('it returns 403 without view permission', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    // Disable personal org auto-creation so the user is not an org owner, which
    // would grant them all org permissions via the TenantContext branch.
    config(['tenancy.auto_create_personal_organization' => false]);

    $user = User::factory()->withoutTwoFactor()->create();
    // Override the auto-assigned 'admin' role (which gets view permission) with
    // the 'user' role, which has no manager-brief permissions.
    $user->syncRoles(['user']);
    // Reload so Spatie's in-memory permission/role cache is cleared.
    $user = $user->fresh();

    $this->actingAs($user)
        ->get(route('manager-brief.index'))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// 16.2 — Cache hit: renders Inertia page, includes cached actions + live widgets
// ---------------------------------------------------------------------------

test('it renders inertia page with cached payload when cache hit', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $siding = Siding::factory()->create(['organization_id' => $org->id]);

    $card = new ActionCard(
        severity: 'high',
        title: 'Priority action',
        why: 'Because of reasons.',
        rsAtStake: 5000.0,
        deepLink: '/dashboard',
        deadline: null,
    );

    $payload = new Payload(
        actions: [$card],
        generatedAt: CarbonImmutable::now(),
        sidingId: $siding->id,
        modelUsed: 'openai/gpt-4o-mini',
        aiStatus: 'ok',
        failedReason: null,
    );
    Cache::put("manager-brief:{$siding->id}:v1", $payload->toArray(), 3600);

    SidingContext::set($siding);

    $user = User::factory()->withoutTwoFactor()->create();
    $user->givePermissionTo('sections.manager_brief.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->get(route('manager-brief.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('manager-brief/index')
            ->has('actions', 1)
            ->where('actions.0.title', 'Priority action')
            ->has('ai_status')
            ->where('ai_status', 'ok')
            ->has('live_exposure')
            ->has('operator_scoreboard')
            ->has('pending_queue')
            ->has('trend_strip')
        );

    SidingContext::flush();
});

// ---------------------------------------------------------------------------
// 16.3 — Cache miss: dispatches async refresh, serves placeholder
// ---------------------------------------------------------------------------

test('it dispatches async refresh and serves placeholder when cache miss', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $siding = Siding::factory()->create(['organization_id' => $org->id]);

    Cache::flush();
    Bus::fake();

    SidingContext::set($siding);

    $user = User::factory()->withoutTwoFactor()->create();
    $user->givePermissionTo('sections.manager_brief.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->get(route('manager-brief.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('manager-brief/index')
            ->where('actions', [])
            ->where('ai_status', 'pending')
        );

    // Artisan::queue dispatches a QueuedCommand job wrapping the command name + args
    Bus::assertDispatched(
        QueuedCommand::class,
        fn (QueuedCommand $job): bool => $job->displayName() === 'manager-brief:refresh',
    );

    SidingContext::flush();
});

// ---------------------------------------------------------------------------
// 16.4 — Live widgets appear alongside AI brief
// ---------------------------------------------------------------------------

test('it serves live widgets alongside ai brief', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $siding = Siding::factory()->create(['organization_id' => $org->id]);

    $payload = new Payload(
        actions: [],
        generatedAt: CarbonImmutable::now(),
        sidingId: $siding->id,
        modelUsed: 'openai/gpt-4o-mini',
        aiStatus: 'ok',
        failedReason: null,
    );
    Cache::put("manager-brief:{$siding->id}:v1", $payload->toArray(), 3600);

    SidingContext::set($siding);

    $user = User::factory()->withoutTwoFactor()->create();
    $user->givePermissionTo('sections.manager_brief.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->get(route('manager-brief.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('manager-brief/index')
            ->has('actions')
            ->has('live_exposure')
            ->has('operator_scoreboard')
            ->has('pending_queue')
            ->has('trend_strip')
            ->has('generated_at')
            ->has('ai_status')
            ->has('widget_errors')
            ->has('can_refresh')
        );

    SidingContext::flush();
});

// ---------------------------------------------------------------------------
// 16.5 — Widget failure: null tile + slug in widget_errors, others populated
// ---------------------------------------------------------------------------

test('it falls back to null tile when one widget throws', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $siding = Siding::factory()->create(['organization_id' => $org->id]);

    $payload = new Payload(
        actions: [],
        generatedAt: CarbonImmutable::now(),
        sidingId: $siding->id,
        modelUsed: 'openai/gpt-4o-mini',
        aiStatus: 'ok',
        failedReason: null,
    );
    Cache::put("manager-brief:{$siding->id}:v1", $payload->toArray(), 3600);

    // Swap TrendStrip for a stub that always throws
    app()->bind(TrendStrip::class, fn (): object => new class()
    {
        public function handle(int $sidingId): array
        {
            throw new RuntimeException('TrendStrip deliberately broken for test.');
        }
    });

    SidingContext::set($siding);

    $user = User::factory()->withoutTwoFactor()->create();
    $user->givePermissionTo('sections.manager_brief.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->get(route('manager-brief.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('manager-brief/index')
            ->where('trend_strip', null)
            ->where('widget_errors', ['trend_strip'])
            // The other three widgets are still present (not null)
            ->whereNot('live_exposure', null)
            ->whereNot('operator_scoreboard', null)
            ->whereNot('pending_queue', null)
        );

    SidingContext::flush();
});

// ---------------------------------------------------------------------------
// 16.6 — Refresh endpoint: 202 + dispatched for user with refresh permission
// ---------------------------------------------------------------------------

test('it dispatches refresh and returns 202 when user has refresh permission', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $siding = Siding::factory()->create(['organization_id' => $org->id]);

    Bus::fake();

    SidingContext::set($siding);

    $user = User::factory()->withoutTwoFactor()->create();
    $user->givePermissionTo([
        'sections.manager_brief.view',
        'sections.manager_brief.refresh',
    ]);
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    $this->actingAs($user)
        ->post(route('manager-brief.refresh'))
        ->assertStatus(202)
        ->assertJson(['dispatched' => true]);

    Bus::assertDispatched(
        QueuedCommand::class,
        fn (QueuedCommand $job): bool => $job->displayName() === 'manager-brief:refresh',
    );

    SidingContext::flush();
});

// ---------------------------------------------------------------------------
// 16.8 — Super-admin siding fallback is scoped to the current tenant
// ---------------------------------------------------------------------------

test('it scopes super admin siding fallback to current tenant', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    // Two organisations each with one siding.
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();

    $sidingA = Siding::factory()->create(['organization_id' => $orgA->id]);
    Siding::factory()->create(['organization_id' => $orgB->id]);

    // No explicit SidingContext — super-admin fallback must engage.
    SidingContext::flush();

    // Set tenant context to orgA → super-admin should only see sidingA.
    TenantContext::set($orgA);

    $payload = new Payload(
        actions: [],
        generatedAt: CarbonImmutable::now(),
        sidingId: $sidingA->id,
        modelUsed: 'openai/gpt-4o-mini',
        aiStatus: 'ok',
        failedReason: null,
    );
    Cache::put("manager-brief:{$sidingA->id}:v1", $payload->toArray(), 3600);

    // Create a super-admin user: isSuperAdmin() queries for team_foreign_key = 0,
    // so we must assign the global-team (organization_id = 0) 'super-admin' role.
    $superAdmin = User::factory()->withoutTwoFactor()->create();

    // Set the Spatie team to the global team (0) so the role assignment lands at
    // organization_id = 0, which is what isSuperAdmin() checks.
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(0);
    $superAdmin->assignRole('super-admin');
    resolve(PermissionRegistrar::class)->setPermissionsTeamId(null);
    resolve(PermissionRegistrar::class)->forgetCachedPermissions();

    // Attach to orgA so SetTenantContext middleware's belongsToOrganization() check
    // passes when it reads TenantContext::check() = true for orgA.
    $superAdmin->organizations()->attach($orgA->id);
    $superAdmin = $superAdmin->fresh();

    // TenantContext is already set to orgA (above). The middleware short-circuits
    // on TenantContext::check() && user->belongsToOrganization(orgA) = true.
    // Super-admin has bypass-permissions so the view check passes.
    // isSuperAdmin() = true → resolveSiding() fallback path engages with orgA filter.
    $this->actingAs($superAdmin)
        ->get(route('manager-brief.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('manager-brief/index')
            ->where('siding.id', $sidingA->id)
        );
});

// ---------------------------------------------------------------------------
// 16.9 — Concurrent cache-miss requests dispatch the refresh command only once
// ---------------------------------------------------------------------------

test('it dispatches refresh command exactly once on concurrent cache miss requests', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $siding = Siding::factory()->create(['organization_id' => $org->id]);

    Cache::flush();
    Bus::fake();

    SidingContext::set($siding);

    $user = User::factory()->withoutTwoFactor()->create();
    $user->givePermissionTo('sections.manager_brief.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);

    // Simulate two concurrent requests by calling the controller endpoint twice
    // in quick succession (both should see a cache miss).
    $this->actingAs($user)->get(route('manager-brief.index'))->assertOk();
    $this->actingAs($user)->get(route('manager-brief.index'))->assertOk();

    // The refresh command must be dispatched exactly once despite two cache misses.
    Bus::assertDispatchedTimes(QueuedCommand::class, 1);
    Bus::assertDispatched(
        QueuedCommand::class,
        fn (QueuedCommand $job): bool => $job->displayName() === 'manager-brief:refresh',
    );
});

// ---------------------------------------------------------------------------
// 16.7 — Refresh endpoint: 403 without refresh permission
// ---------------------------------------------------------------------------

test('it returns 403 from refresh without refresh permission', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ManagerBriefPermissionsSeeder::class);

    $org = Organization::factory()->create();
    $siding = Siding::factory()->create(['organization_id' => $org->id]);

    SidingContext::set($siding);

    // Disable personal org auto-creation so the user is not an org owner, which
    // would grant them all org permissions via the TenantContext branch.
    config(['tenancy.auto_create_personal_organization' => false]);

    // Override the auto-assigned 'admin' role with 'user', then grant only view.
    $user = User::factory()->withoutTwoFactor()->create();
    $user->syncRoles(['user']);
    $user->givePermissionTo('sections.manager_brief.view');
    $user->sidings()->attach($siding->id, ['is_primary' => true]);
    // Reload so Spatie's in-memory permission/role cache is cleared.
    $user = $user->fresh();

    $this->actingAs($user)
        ->post(route('manager-brief.refresh'))
        ->assertForbidden();

    SidingContext::flush();
});
