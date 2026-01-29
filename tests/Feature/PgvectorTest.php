<?php

declare(strict_types=1);

use App\Models\EmbeddingDemo;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

test('pgvector extension and embedding_demos table exist when using pgsql', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('pgvector tests require PostgreSQL.');
    }

    expect(DB::getSchemaBuilder()->hasTable('embedding_demos'))->toBeTrue();
});

test('embedding demos can store and query by vector distance when using pgsql', function (): void {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('pgvector tests require PostgreSQL.');
    }

    EmbeddingDemo::query()->delete();

    EmbeddingDemo::query()->create([
        'content' => 'item one',
        'embedding' => new Vector([1, 1, 1]),
    ]);
    EmbeddingDemo::query()->create([
        'content' => 'item two',
        'embedding' => new Vector([2, 2, 2]),
    ]);
    EmbeddingDemo::query()->create([
        'content' => 'item three',
        'embedding' => new Vector([1, 1, 2]),
    ]);

    $neighbors = EmbeddingDemo::query()
        ->nearestNeighbors('embedding', [1, 1, 1], Distance::L2)
        ->take(3)
        ->get();

    expect($neighbors->pluck('content')->toArray())->toBe(['item one', 'item three', 'item two']);
    expect($neighbors->first()->embedding)->toBeInstanceOf(Vector::class);
});
