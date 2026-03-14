<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\FunnelTemplate;
use Illuminate\Database\Seeder;

final class FunnelTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (FunnelTemplate::query()->exists()) {
            return;
        }
    }
}
