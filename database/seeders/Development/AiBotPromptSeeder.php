<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiBotPrompt;
use Illuminate\Database\Seeder;

final class AiBotPromptSeeder extends Seeder
{
    public function run(): void
    {
        if (AiBotPrompt::query()->exists()) {
            return;
        }
    }
}
