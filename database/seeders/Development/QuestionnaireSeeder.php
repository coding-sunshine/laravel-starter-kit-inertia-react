<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Questionnaire;
use Illuminate\Database\Seeder;

final class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        if (Questionnaire::query()->exists()) {
            return;
        }
    }
}
