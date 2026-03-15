<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactSubmission;
use App\Models\Lot;
use App\Models\Organization;
use App\Models\Project;
use App\Models\PropertyReservation;
use App\Models\Sale;
use App\Models\Task;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $isAdmin = $user->hasRole('admin') || $user->hasRole('piab_admin');
        $isAgent = $user->hasRole('agent') || $user->hasRole('sales_agent') || $user->hasRole('bdm');

        $props = [];

        // CRM KPIs (available to all CRM roles)
        if ($isSuperAdmin || $isAdmin || $isAgent) {
            $agentId = ($isAgent && ! $isAdmin && ! $isSuperAdmin) ? $user->id : null;

            $props['crmKpis'] = Inertia::defer(fn () => $this->crmKpis($agentId));
            $props['pipelineFunnel'] = Inertia::defer(fn () => $this->pipelineFunnel($agentId));
            $props['aiInsight'] = Cache::get('dashboard_insight');
            $props['featuredProjects'] = Inertia::defer(fn () => $this->featuredProjects());
        }

        if ($isSuperAdmin) {
            $props['usersCount'] = User::query()->count();
            $props['orgsCount'] = Organization::query()->count();
            $props['contactSubmissionsCount'] = ContactSubmission::query()->count();
            $props['usersGrowthPercent'] = $this->weeklyGrowthPercent(fn () => User::query());
            $props['orgsGrowthPercent'] = $this->weeklyGrowthPercent(fn () => Organization::query());
        }

        $props['weeklyStats'] = Inertia::defer(fn (): array => $this->weeklyStats());

        return Inertia::render('dashboard', $props);
    }

    /** @return array<string, int|string> */
    private function crmKpis(?int $agentId): array
    {
        $weekAgo = Date::now()->subDays(7);

        $newContacts = Contact::query()->where('created_at', '>=', $weekAgo)->count();
        $newContactsLast = Contact::query()
            ->whereBetween('created_at', [Date::now()->subDays(14), $weekAgo])
            ->count();
        $newContactsDelta = $newContactsLast > 0
            ? (int) round(($newContacts - $newContactsLast) / $newContactsLast * 100)
            : ($newContacts > 0 ? 100 : 0);

        $activeReservations = PropertyReservation::query()
            ->whereNotIn('stage', ['settled', 'cancelled'])
            ->count();

        $settledThisMonth = '$'.number_format(
            (float) Sale::query()
                ->where('status', 'settled')
                ->where('settled_at', '>=', Date::now()->startOfMonth())
                ->sum('comms_in_total'),
            0
        );

        $tasksQuery = Task::query()
            ->where('due_at', '<', Date::now())
            ->where('status', '!=', 'done');
        if ($agentId !== null) {
            $tasksQuery->where('assigned_to_user_id', $agentId);
        }
        $overdueTasksCount = $tasksQuery->count();

        $staleContacts = Contact::query()
            ->where(function (Builder $q): void {
                $q->whereNull('last_contacted_at')
                    ->orWhere('last_contacted_at', '<', Date::now()->subDays(30));
            })
            ->count();

        $totalContacts = Contact::query()->count();

        $priorityQueue = Contact::query()
            ->select(['id', 'first_name', 'last_name', 'stage', 'contact_origin', 'lead_score', 'last_contacted_at', 'created_at'])
            ->orderByRaw('last_contacted_at IS NULL DESC, last_contacted_at ASC')
            ->limit(7)
            ->get()
            ->map(fn (Contact $c): array => [
                'id' => $c->id,
                'name' => mb_trim($c->first_name.' '.$c->last_name),
                'stage' => $c->stage ?? 'New',
                'source' => $c->contact_origin ?? 'property',
                'leadScore' => $c->lead_score ?? 0,
                'daysSince' => $c->last_contacted_at
                    ? (int) abs(Date::now()->diffInDays($c->last_contacted_at))
                    : (int) abs(Date::now()->diffInDays($c->created_at)),
            ]);

        return [
            'newContactsThisWeek' => $newContacts,
            'newContactsDelta' => $newContactsDelta,
            'activeReservations' => $activeReservations,
            'settledThisMonth' => $settledThisMonth,
            'overdueTasksCount' => $overdueTasksCount,
            'staleContacts' => $staleContacts,
            'totalContacts' => $totalContacts,
            'totalLotsAvailable' => Lot::query()->where('is_archived', false)->count(),
            'totalLotsSold' => Lot::query()->where('is_archived', true)->count(),
            'priorityQueue' => $priorityQueue,
        ];
    }

    /** @return array<int, array{stage: string, count: int}> */
    private function pipelineFunnel(?int $agentId): array
    {
        $stages = ['enquiry', 'qualified', 'reservation', 'unconditional', 'contract', 'settled'];
        $query = PropertyReservation::query();

        $counts = $query
            ->selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage');

        return collect($stages)->map(fn (string $stage) => [
            'stage' => ucfirst($stage),
            'count' => (int) ($counts[$stage] ?? 0),
        ])->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function featuredProjects(): array
    {
        return Project::query()
            ->where('is_featured', true)
            ->whereNull('deleted_at')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->with('media')
            ->get(['id', 'title', 'suburb', 'state', 'min_price', 'max_price', 'rent_yield', 'total_lots', 'stage'])
            ->map(fn (Project $p): array => [
                'id' => $p->id,
                'title' => $p->title,
                'location' => mb_trim(($p->suburb ?? '').', '.($p->state ?? ''), ', '),
                'priceRange' => $p->min_price && $p->max_price
                    ? '$'.number_format((float) $p->min_price).' - $'.number_format((float) $p->max_price)
                    : ($p->min_price ? '$'.number_format((float) $p->min_price) : null),
                'image' => rescue(fn () => $p->getFirstMediaUrl('photo'), null),
                'type' => $p->stage,
                'rentYield' => $p->rent_yield ? number_format((float) $p->rent_yield, 2).'%' : null,
                'totalLots' => $p->total_lots,
                'stage' => $p->stage,
            ])
            ->all();
    }

    /** @param Closure(): Builder<Model> $factory */
    private function weeklyGrowthPercent(Closure $factory): ?int
    {
        $thisWeek = $factory()
            ->whereBetween('created_at', [Date::today()->subDays(6)->startOfDay(), Date::now()])
            ->count();

        $lastWeek = $factory()
            ->whereBetween('created_at', [Date::today()->subDays(13)->startOfDay(), Date::today()->subDays(7)->endOfDay()])
            ->count();

        if ($lastWeek === 0) {
            return $thisWeek > 0 ? 100 : null;
        }

        return (int) round((($thisWeek - $lastWeek) / $lastWeek) * 100);
    }

    /** @return array<int, array{name: string, value: int}> */
    private function weeklyStats(): array
    {
        $days = collect(range(6, 0))->map(fn (int $i) => Date::today()->subDays($i));

        $signups = User::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $days->first())
            ->groupBy('date')
            ->pluck('count', 'date');

        return $days->map(fn (\Carbon\CarbonImmutable $day): array => [
            'name' => $day->format('D'),
            'value' => (int) ($signups[$day->toDateString()] ?? 0),
        ])->values()->all();
    }
}
