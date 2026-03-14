<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\EmailCampaign;
use Illuminate\Database\Seeder;

final class EmailCampaignSeeder extends Seeder
{
    public function run(): void
    {
        if (EmailCampaign::query()->exists()) {
            return;
        }

        EmailCampaign::factory()->count(3)->create();
    }
}
