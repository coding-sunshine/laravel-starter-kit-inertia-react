<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\WebsiteContact;
use Illuminate\Database\Seeder;

final class WebsiteContactSeeder extends Seeder
{
    public function run(): void
    {
        if (WebsiteContact::query()->exists()) {
            return;
        }
    }
}
