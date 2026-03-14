<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ResourceCategory;
use Illuminate\Database\Seeder;

final class ResourceCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (ResourceCategory::query()->exists()) {
            return;
        }
    }
}
