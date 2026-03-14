<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\MailList;
use Illuminate\Database\Seeder;

final class MailListSeeder extends Seeder
{
    public function run(): void
    {
        if (MailList::query()->exists()) {
            return;
        }
    }
}
