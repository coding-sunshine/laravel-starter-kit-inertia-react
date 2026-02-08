<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ContactSubmission;
use Illuminate\Database\Seeder;

final class ContactSubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        if (ContactSubmission::query()->exists()) {
            return;
        }

        ContactSubmission::factory()
            ->count(5)
            ->create();
    }
}
