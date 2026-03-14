<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Partner;
use Illuminate\Database\Seeder;

final class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        if (Partner::query()->exists()) {
            return;
        }
    }
}
