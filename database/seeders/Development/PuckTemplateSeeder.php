<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\PuckTemplate;
use Illuminate\Database\Seeder;

final class PuckTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (PuckTemplate::query()->exists()) {
            return;
        }
    }
}
