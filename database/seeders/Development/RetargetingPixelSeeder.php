<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\RetargetingPixel;
use Illuminate\Database\Seeder;

final class RetargetingPixelSeeder extends Seeder
{
    public function run(): void
    {
        if (RetargetingPixel::query()->exists()) {
            return;
        }

        RetargetingPixel::factory()->count(3)->create();
    }
}
