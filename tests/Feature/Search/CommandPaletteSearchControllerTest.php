<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;

it('rejects unauthenticated requests', function (): void {
    $this->getJson('/api/command-palette/search?q=DUMK')
        ->assertStatus(401);
});

it('returns the expected JSON envelope', function (): void {
    $user = User::factory()->create();
    $siding = Siding::factory()->create();
    Rake::factory()->create(['siding_id' => $siding->id, 'rake_number' => 'DUMK-1234']);

    $this->actingAs($user)
        ->getJson('/api/command-palette/search?q=DUMK')
        ->assertOk()
        ->assertJsonStructure([
            'rakes' => [['id', 'rake_number', 'siding_name', 'status']],
            'indents',
            'rrs',
        ])
        ->assertJsonPath('rakes.0.rake_number', 'DUMK-1234');
});

it('returns empty arrays when query is too short', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/command-palette/search?q=a')
        ->assertOk()
        ->assertJson(['rakes' => [], 'indents' => [], 'rrs' => []]);
});
