<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CrmComment;
use Illuminate\Database\Seeder;

final class CrmCommentSeeder extends Seeder
{
    public function run(): void
    {
        if (CrmComment::query()->exists()) {
            return;
        }
    }
}
