<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\LeadScore;
use Illuminate\Database\Seeder;

final class LeadScoreSeeder extends Seeder
{
    public function run(): void
    {
        if (LeadScore::exists()) {
            return;
        }
    }
}
