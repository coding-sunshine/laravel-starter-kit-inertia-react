<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ColdOutreachTemplate;
use Illuminate\Database\Seeder;

final class ColdOutreachTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (ColdOutreachTemplate::exists()) {
            return;
        }
    }
}
