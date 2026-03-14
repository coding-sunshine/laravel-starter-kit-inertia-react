<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\NurtureSequence;
use Illuminate\Database\Seeder;

final class NurtureSequenceSeeder extends Seeder
{
    public function run(): void
    {
        if (NurtureSequence::exists()) {
            return;
        }
    }
}
