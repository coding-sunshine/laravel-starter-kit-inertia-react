<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\BrochureMailJob;
use Illuminate\Database\Seeder;

final class BrochureMailJobSeeder extends Seeder
{
    public function run(): void
    {
        if (BrochureMailJob::query()->exists()) {
            return;
        }
    }
}
