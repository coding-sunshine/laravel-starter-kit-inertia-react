<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\EngagementEvent;
use Illuminate\Database\Seeder;

final class EngagementEventSeeder extends Seeder
{
    public function run(): void
    {
        if (EngagementEvent::exists()) {
            return;
        }
    }
}
