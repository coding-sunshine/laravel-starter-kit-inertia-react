<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ContactEmail;
use Illuminate\Database\Seeder;

final class ContactEmailSeeder extends Seeder
{
    public function run(): void
    {
        if (ContactEmail::query()->exists()) {
            return;
        }

        ContactEmail::factory()
            ->count(30)
            ->create();
    }
}
