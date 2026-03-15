<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\OnboardingProgress;
use Illuminate\Database\Seeder;

final class OnboardingProgressSeeder extends Seeder
{
    public function run(): void
    {
        if (OnboardingProgress::query()->exists()) {
            return;
        }

        // OnboardingProgress rows are created dynamically on signup — no dev seed data needed.
    }
}
