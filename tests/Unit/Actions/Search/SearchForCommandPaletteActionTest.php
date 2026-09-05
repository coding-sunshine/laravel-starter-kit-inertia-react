<?php

declare(strict_types=1);

use App\Actions\Search\SearchForCommandPaletteAction;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\Siding;

beforeEach(function (): void {
    $this->action = app(SearchForCommandPaletteAction::class);
});

it('returns empty results for short queries', function (): void {
    $results = $this->action->handle('a');
    expect($results->rakes)->toBe([])
        ->and($results->indents)->toBe([])
        ->and($results->rrs)->toBe([]);
});

it('finds rakes by partial rake_number', function (): void {
    $siding = Siding::factory()->create(['name' => 'Dumka']);
    Rake::factory()->create(['siding_id' => $siding->id, 'rake_number' => 'DUMK-12345']);
    Rake::factory()->create(['siding_id' => $siding->id, 'rake_number' => 'DUMK-67890']);

    $results = $this->action->handle('123');
    expect($results->rakes)->toHaveCount(1)
        ->and($results->rakes[0]['rake_number'])->toBe('DUMK-12345');
});

it('caps results at 10 per category', function (): void {
    $siding = Siding::factory()->create();
    Rake::factory()->count(15)->create([
        'siding_id' => $siding->id,
        'rake_number' => fn () => 'RAK-'.fake()->unique()->numberBetween(10000, 99999),
    ]);

    $results = $this->action->handle('RAK');
    expect($results->rakes)->toHaveCount(10);
});

it('searches indents by indent_number and e_demand_reference_id', function (): void {
    Indent::factory()->create(['indent_number' => 'IND-7777', 'e_demand_reference_id' => 'ED-1234']);

    $byIndent = $this->action->handle('7777');
    $byEDemand = $this->action->handle('ED-12');

    expect($byIndent->indents)->toHaveCount(1)
        ->and($byEDemand->indents)->toHaveCount(1);
});
