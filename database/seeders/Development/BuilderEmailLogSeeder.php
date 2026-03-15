<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\BuilderEmailLog;
use Illuminate\Database\Seeder;

final class BuilderEmailLogSeeder extends Seeder
{
    public function run(): void
    {
        if (BuilderEmailLog::query()->exists()) {
            return;
        }
    }
}
