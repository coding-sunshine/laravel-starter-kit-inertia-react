<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\WidgetSetting;
use Illuminate\Database\Seeder;

final class WidgetSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (WidgetSetting::query()->exists()) {
            return;
        }
    }
}
