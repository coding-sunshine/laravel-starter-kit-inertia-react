<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ResourceGroup;
use Illuminate\Database\Seeder;

final class ResourceGroupSeeder extends Seeder
{
    public function run(): void
    {
        if (ResourceGroup::query()->exists()) {
            return;
        }
    }
}
