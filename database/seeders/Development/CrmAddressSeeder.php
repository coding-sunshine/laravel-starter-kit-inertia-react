<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CrmAddress;
use Illuminate\Database\Seeder;

final class CrmAddressSeeder extends Seeder
{
    public function run(): void
    {
        if (CrmAddress::query()->exists()) {
            return;
        }
    }
}
