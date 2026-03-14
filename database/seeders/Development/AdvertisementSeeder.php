<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;

final class AdvertisementSeeder extends Seeder
{
    public function run(): void
    {
        if (Advertisement::query()->exists()) {
            return;
        }
    }
}
