<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Company;
use Illuminate\Database\Seeder;

final class CompanySeeder extends Seeder
{
    public function run(): void
    {
        if (Company::query()->exists()) {
            return;
        }

        Company::factory()
            ->count(15)
            ->create();
    }
}
