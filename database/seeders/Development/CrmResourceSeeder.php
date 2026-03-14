<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CrmResource;
use Illuminate\Database\Seeder;

final class CrmResourceSeeder extends Seeder
{
    public function run(): void
    {
        if (CrmResource::query()->exists()) {
            return;
        }
    }
}
