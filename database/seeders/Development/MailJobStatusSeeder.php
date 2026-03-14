<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\MailJobStatus;
use Illuminate\Database\Seeder;

final class MailJobStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (MailJobStatus::query()->exists()) {
            return;
        }
    }
}
