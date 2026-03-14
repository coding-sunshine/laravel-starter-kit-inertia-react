<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiBotRun;
use Illuminate\Database\Seeder;

final class AiBotRunSeeder extends Seeder
{
    public function run(): void
    {
        if (AiBotRun::query()->exists()) {
            return;
        }
    }
}
