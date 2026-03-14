<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ContactPhone;
use Illuminate\Database\Seeder;

final class ContactPhoneSeeder extends Seeder
{
    public function run(): void
    {
        if (ContactPhone::query()->exists()) {
            return;
        }

        ContactPhone::factory()
            ->count(30)
            ->create();
    }
}
