<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sales\Widgets;

use App\Models\Contact;
use App\Models\Sale;
use App\Services\TenantContext;
use Filament\Widgets\Widget;

final class CommissionSummaryWidget extends Widget
{
    protected string $view = 'filament.resources.sales.widgets.commission-summary';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public function getViewData(): array
    {
        if (! TenantContext::check()) {
            return ['totalCommission' => 0, 'byAgent' => []];
        }
        $total = (float) Sale::query()
            ->selectRaw('COALESCE(SUM(COALESCE(comms_in_total, 0) + COALESCE(comms_out_total, 0)), 0) as total')
            ->value('total');
        $byAgent = Sale::query()
            ->selectRaw('sales_agent_contact_id, COALESCE(SUM(COALESCE(comms_in_total, 0) + COALESCE(comms_out_total, 0)), 0) as total')
            ->whereNotNull('sales_agent_contact_id')
            ->groupBy('sales_agent_contact_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->mapWithKeys(function ($row) {
                $contact = Contact::query()->find($row->sales_agent_contact_id);
                $name = $contact ? trim($contact->first_name.' '.$contact->last_name) : "Contact #{$row->sales_agent_contact_id}";
                return [$name => (float) $row->total];
            })
            ->all();

        return [
            'totalCommission' => round($total, 2),
            'byAgent' => $byAgent,
        ];
    }

    public static function canView(): bool
    {
        return TenantContext::check();
    }
}
