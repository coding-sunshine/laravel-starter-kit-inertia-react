<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Relationship;
use Illuminate\Database\Seeder;

final class RelationshipSeeder extends Seeder
{
    public function run(): void
    {
        if (Relationship::query()->exists()) {
            return;
        }
    }
}
