<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CampaignWebsite;
use Illuminate\Database\Seeder;

final class CampaignWebsiteSeeder extends Seeder
{
    public function run(): void
    {
        if (CampaignWebsite::query()->exists()) {
            return;
        }
    }
}
