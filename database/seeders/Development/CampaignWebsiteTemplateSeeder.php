<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CampaignWebsiteTemplate;
use Illuminate\Database\Seeder;

final class CampaignWebsiteTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (CampaignWebsiteTemplate::query()->exists()) {
            return;
        }
    }
}
