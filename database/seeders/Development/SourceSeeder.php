<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Source;
use Illuminate\Database\Seeder;

final class SourceSeeder extends Seeder
{
    public function run(): void
    {
        if (Source::query()->exists()) {
            return;
        }

        Source::factory()
            ->count(10)
            ->create();
    }
}
