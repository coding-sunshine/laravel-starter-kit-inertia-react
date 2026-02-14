<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\VoucherScope;
use Illuminate\Database\Seeder;

final class VoucherScopeSeeder extends Seeder
{
    public function run(): void
    {
        if (VoucherScope::query()->where('name', 'Global')->exists()) {
            return;
        }

        VoucherScope::query()->create(['name' => 'Global']);
    }
}
