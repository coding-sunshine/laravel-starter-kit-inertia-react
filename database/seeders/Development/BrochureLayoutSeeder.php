<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\BrochureLayout;
use Illuminate\Database\Seeder;

final class BrochureLayoutSeeder extends Seeder
{
    public function run(): void
    {
        if (BrochureLayout::query()->exists()) {
            return;
        }

        BrochureLayout::factory()->count(3)->create();
    }
}
