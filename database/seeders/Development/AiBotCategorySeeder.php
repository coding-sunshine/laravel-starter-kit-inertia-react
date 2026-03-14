<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiBotCategory;
use Illuminate\Database\Seeder;

final class AiBotCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (AiBotCategory::query()->exists()) {
            return;
        }
    }
}
