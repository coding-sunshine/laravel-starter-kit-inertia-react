<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\XeroConnection;
use Illuminate\Database\Seeder;

final class XeroConnectionSeeder extends Seeder
{
    public function run(): void
    {
        if (XeroConnection::query()->exists()) {
            return;
        }

        XeroConnection::query()->create([
            'xero_tenant_id' => 'demo-tenant-'.uniqid(),
            'xero_tenant_name' => 'Demo Xero Org',
            'connected_at' => now(),
        ]);
    }
}
