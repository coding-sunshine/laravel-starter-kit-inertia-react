<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\PaymentStage;
use Illuminate\Database\Seeder;

final class PaymentStageSeeder extends Seeder
{
    public function run(): void
    {
        if (PaymentStage::query()->exists()) {
            return;
        }

        PaymentStage::factory()->count(10)->create();
    }
}
