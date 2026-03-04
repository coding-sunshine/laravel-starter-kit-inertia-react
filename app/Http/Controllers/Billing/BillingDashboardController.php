<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Models\Billing\BillingMetric;
use App\Models\Billing\Plan;
use App\Services\TenantContext;
use Inertia\Inertia;
use Inertia\Response;

final readonly class BillingDashboardController
{
    public function index(): Response
    {
        $organization = TenantContext::get();
        abort_unless($organization, 403, 'No organization selected.');

        $orgId = $organization->id;
        $usageChartData = BillingMetric::query()
            ->where('organization_id', $orgId)
            ->where('date', '>=', \Illuminate\Support\Facades\Date::now()->subMonths(6))
            ->orderBy('date')
            ->get(['date', 'credits_used'])
            ->map(fn ($m): array => [
                'month' => $m->date->format('M Y'),
                'credits' => $m->credits_used,
            ])
            ->values()
            ->all();

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'description', 'price', 'currency', 'invoice_interval', 'is_per_seat', 'price_per_seat'])
            ->map(fn ($p): array => [
                'id' => $p->id,
                'name' => is_array($p->name) ? ($p->name['en'] ?? (string) reset($p->name)) : (string) $p->name,
                'description' => is_array($p->description) ? ($p->description['en'] ?? (string) reset($p->description)) : (string) ($p->description ?? ''),
                'price' => (float) $p->price,
                'currency' => (string) ($p->currency ?? 'usd'),
                'interval' => (string) ($p->invoice_interval ?? 'month'),
                'is_per_seat' => (bool) $p->is_per_seat,
                'price_per_seat' => $p->price_per_seat ? (float) $p->price_per_seat : null,
            ])
            ->values()
            ->all();

        return Inertia::render('billing/index', [
            'organization' => $organization->only(['id', 'name', 'billing_email']),
            'creditBalance' => $organization->creditBalance(),
            'activePlan' => $organization->activePlan()?->only(['id', 'name']) ?? null,
            'isOnTrial' => $organization->isOnTrial(),
            'invoices' => $organization->billingHistory(5),
            'usageChartData' => $usageChartData,
            'plans' => $plans,
        ]);
    }
}
