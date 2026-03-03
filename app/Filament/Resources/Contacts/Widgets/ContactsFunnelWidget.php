<?php

declare(strict_types=1);

namespace App\Filament\Resources\Contacts\Widgets;

use App\Models\Contact;
use App\Services\TenantContext;
use Filament\Widgets\Widget;

final class ContactsFunnelWidget extends Widget
{
    protected string $view = 'filament.resources.contacts.widgets.contacts-funnel';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    public ?array $stageCounts = null;

    public function getViewData(): array
    {
        if (! TenantContext::check()) {
            return ['stageCounts' => []];
        }
        $counts = Contact::query()
            ->selectRaw('stage, count(*) as count')
            ->whereNotNull('stage')
            ->where('stage', '!=', '')
            ->groupBy('stage')
            ->orderByDesc('count')
            ->pluck('count', 'stage')
            ->all();
        $total = array_sum($counts);

        return [
            'stageCounts' => $counts,
            'totalWithStage' => $total,
            'contactsTotal' => Contact::query()->count(),
        ];
    }

    public static function canView(): bool
    {
        return TenantContext::check();
    }
}
