<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Contact;
use Illuminate\Database\Seeder;

final class ContactSeeder extends Seeder
{
    public function run(): void
    {
        if (Contact::query()->exists()) {
            return;
        }

        Contact::factory()
            ->count(20)
            ->create();
    }
}
