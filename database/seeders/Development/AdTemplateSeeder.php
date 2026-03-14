<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AdTemplate;
use Illuminate\Database\Seeder;

final class AdTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (AdTemplate::query()->exists()) {
            return;
        }

        AdTemplate::factory()->count(3)->create();
    }
}
