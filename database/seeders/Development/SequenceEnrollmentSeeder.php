<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\SequenceEnrollment;
use Illuminate\Database\Seeder;

final class SequenceEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        if (SequenceEnrollment::exists()) {
            return;
        }
    }
}
