<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CrmNote;
use Illuminate\Database\Seeder;

final class CrmNoteSeeder extends Seeder
{
    public function run(): void
    {
        if (CrmNote::query()->exists()) {
            return;
        }
    }
}
