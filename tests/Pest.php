<?php

declare(strict_types=1);

use App\Testing\SeedHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Str::createRandomStringsNormally();
        Str::createUuidsNormally();
        Http::preventStrayRequests();
        Process::preventStrayProcesses();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * Seed a model and its relationships for testing.
 *
 * @param  class-string<Illuminate\Database\Eloquent\Model>  $modelClass
 * @return Illuminate\Database\Eloquent\Collection
 */
function seedFor(string $modelClass, int $count = 1)
{
    return SeedHelper::seedFor($modelClass, $count);
}

/**
 * Seed multiple models at once.
 *
 * @param  array<class-string<Illuminate\Database\Eloquent\Model>|array{class: class-string<Illuminate\Database\Eloquent\Model>, count: int}>  $models
 * @return array<string, Illuminate\Database\Eloquent\Collection>
 */
function seedMany(array $models): array
{
    return SeedHelper::seedMany($models);
}

/**
 * Seed using a named scenario.
 *
 * @return array<string, mixed>
 */
function seedScenario(string $scenarioName): array
{
    return SeedHelper::seedScenario($scenarioName);
}

function something(): void
{
    // ..
}
