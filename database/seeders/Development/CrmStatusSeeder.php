<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CrmStatus;
use Illuminate\Database\Seeder;

final class CrmStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (CrmStatus::query()->exists()) {
            return;
        }
    }
}
