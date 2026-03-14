<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\PinnedNote;
use Illuminate\Database\Seeder;

final class PinnedNoteSeeder extends Seeder
{
    public function run(): void
    {
        if (PinnedNote::query()->exists()) {
            return;
        }

        PinnedNote::factory()->count(10)->create();
    }
}
