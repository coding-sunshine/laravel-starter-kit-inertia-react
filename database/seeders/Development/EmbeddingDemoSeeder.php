<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\EmbeddingDemo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Pgvector\Laravel\Vector;

final class EmbeddingDemoSeeder extends Seeder
{
    private array $dependencies = [];

    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->seedDemoVectors();
    }

    /**
     * Seed demo embedding rows for pgvector (idempotent).
     */
    private function seedDemoVectors(): void
    {
        $demos = [
            ['content' => 'item one', 'embedding' => new Vector([1, 1, 1])],
            ['content' => 'item two', 'embedding' => new Vector([2, 2, 2])],
            ['content' => 'item three', 'embedding' => new Vector([1, 1, 2])],
        ];

        foreach ($demos as $demo) {
            EmbeddingDemo::query()->firstOrCreate(
                ['content' => $demo['content']],
                ['embedding' => $demo['embedding']]
            );
        }
    }
}
