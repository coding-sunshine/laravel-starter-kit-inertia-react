<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\LandingPageTemplate;
use Illuminate\Database\Seeder;

final class LandingPageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (LandingPageTemplate::query()->exists()) {
            return;
        }

        LandingPageTemplate::factory()->count(3)->create();
    }
}
