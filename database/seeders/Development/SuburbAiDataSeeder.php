<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\SuburbAiData;
use Illuminate\Database\Seeder;

final class SuburbAiDataSeeder extends Seeder
{
    public function run(): void
    {
        if (SuburbAiData::query()->exists()) {
            return;
        }
    }
}
