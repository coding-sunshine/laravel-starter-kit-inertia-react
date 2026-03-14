<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\FinanceAssessment;
use Illuminate\Database\Seeder;

final class FinanceAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        if (FinanceAssessment::query()->exists()) {
            return;
        }
    }
}
