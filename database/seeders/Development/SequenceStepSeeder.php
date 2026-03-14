<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\SequenceStep;
use Illuminate\Database\Seeder;

final class SequenceStepSeeder extends Seeder
{
    public function run(): void
    {
        if (SequenceStep::exists()) {
            return;
        }
    }
}
