<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\FunnelInstance;
use Illuminate\Database\Seeder;

final class FunnelInstanceSeeder extends Seeder
{
    public function run(): void
    {
        if (FunnelInstance::query()->exists()) {
            return;
        }
    }
}
