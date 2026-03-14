<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\DealDocument;
use Illuminate\Database\Seeder;

final class DealDocumentSeeder extends Seeder
{
    public function run(): void
    {
        if (DealDocument::query()->exists()) {
            return;
        }

        DealDocument::factory()->count(10)->create();
    }
}
