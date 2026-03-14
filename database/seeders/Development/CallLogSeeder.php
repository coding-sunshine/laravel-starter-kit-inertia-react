<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CallLog;
use Illuminate\Database\Seeder;

final class CallLogSeeder extends Seeder
{
    public function run(): void
    {
        if (CallLog::query()->exists()) {
            return;
        }
    }
}
