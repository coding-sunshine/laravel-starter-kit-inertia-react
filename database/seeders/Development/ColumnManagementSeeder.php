<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ColumnManagement;
use Illuminate\Database\Seeder;

final class ColumnManagementSeeder extends Seeder
{
    public function run(): void
    {
        if (ColumnManagement::query()->exists()) {
            return;
        }
    }
}
