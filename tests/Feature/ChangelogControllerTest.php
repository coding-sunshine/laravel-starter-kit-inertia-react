<?php

declare(strict_types=1);

use App\Models\ChangelogEntry;

it('renders changelog index with published entries', function (): void {
    ChangelogEntry::factory()->published()->create(['title' => 'New feature']);

    $response = $this->get(route('changelog.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('changelog/index')
            ->has('entries')
            ->where('entries.data.0.title', 'New feature')
        );
});
