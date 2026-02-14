<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\EnterpriseInquiry;
use Illuminate\Database\Seeder;

final class EnterpriseInquirySeeder extends Seeder
{
    public function run(): void
    {
        EnterpriseInquiry::factory()->count(3)->create();
    }
}
