<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Task;
use Illuminate\Database\Seeder;

final class TaskSeeder extends Seeder
{
    public function run(): void
    {
        if (Task::query()->exists()) {
            return;
        }
    }
}
