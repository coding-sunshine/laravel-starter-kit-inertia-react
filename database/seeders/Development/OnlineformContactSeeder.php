<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\OnlineformContact;
use Illuminate\Database\Seeder;

final class OnlineformContactSeeder extends Seeder
{
    public function run(): void
    {
        if (OnlineformContact::query()->exists()) {
            return;
        }
    }
}
