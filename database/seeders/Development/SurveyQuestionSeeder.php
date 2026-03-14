<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\SurveyQuestion;
use Illuminate\Database\Seeder;

final class SurveyQuestionSeeder extends Seeder
{
    public function run(): void
    {
        if (SurveyQuestion::query()->exists()) {
            return;
        }
    }
}
