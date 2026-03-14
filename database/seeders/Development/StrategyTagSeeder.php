<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\StrategyTag;
use Illuminate\Database\Seeder;

final class StrategyTagSeeder extends Seeder
{
    public function run(): void
    {
        if (StrategyTag::query()->exists()) {
            return;
        }
    }
}
