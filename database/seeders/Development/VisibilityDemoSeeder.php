<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\VisibilityDemo;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;

final class VisibilityDemoSeeder extends Seeder
{
    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        $org = Organization::query()->first();
        if (! $org) {
            return;
        }

        TenantContext::set($org);

        if (! VisibilityDemo::query()->where('title', 'Demo item')->exists()) {
            VisibilityDemo::query()->create([
                'title' => 'Demo item',
            ]);
        }

        TenantContext::forget();
    }
}
