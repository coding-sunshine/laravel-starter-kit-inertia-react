<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiBot;
use Illuminate\Database\Seeder;

final class AiBotSeeder extends Seeder
{
    public function run(): void
    {
        if (AiBot::query()->exists()) {
            return;
        }
    }
}
