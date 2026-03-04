<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\Lot;
use App\Models\Project;
use App\Models\PropertyEnquiry;
use App\Models\PropertyReservation;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class GetDashboardMetrics
{
    public function handle(): array
    {
        $totalProjects = Project::count();
        $totalLots = Lot::count();
        $totalContacts = Contact::count();
        $totalEnquiries = PropertyEnquiry::count();

        // Sales metrics
        $totalSales = Sale::count();
        $totalRevenue = (float) (Sale::sum('comms_in_total') ?? 0);
        $averageDealValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Pipeline metrics
        $pipelineValue = (float) (Project::whereNotIn('stage', ['Completed', 'Cancelled'])
            ->sum(DB::raw('(min_price + max_price) / 2 * total_lots')) ?? 0);

        // Conversion rate
        $conversionRate = $totalEnquiries > 0 ? ($totalSales / $totalEnquiries) * 100 : 0;

        // Monthly metrics
        $monthlyMetrics = $this->getMonthlyMetrics();

        return [
            'kpis' => [
                'total_projects' => $totalProjects,
                'total_lots' => $totalLots,
                'total_contacts' => $totalContacts,
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'average_deal_value' => $averageDealValue,
                'pipeline_value' => $pipelineValue,
                'conversion_rate' => round($conversionRate, 2),
                'monthly_growth' => $monthlyMetrics['growth_percentage'],
            ],
            'charts' => [
                'pipeline' => $this->getPipelineData(),
                'revenue_trends' => $monthlyMetrics['revenue_trends'],
                'project_distribution' => $this->getProjectDistribution(),
                'lead_activity' => $this->getLeadActivity(),
                'geographic_distribution' => $this->getGeographicDistribution(),
            ],
            'recent_activity' => $this->getRecentActivity(),
            'top_projects' => $this->getTopProjects(),
        ];
    }

    private function getMonthlyMetrics(): array
    {
        $currentMonth = (float) (Sale::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('comms_in_total') ?? 0);

        $lastMonth = (float) (Sale::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->sum('comms_in_total') ?? 0);

        $growthPercentage = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;

        // Get revenue trends for the last 12 months
        $revenueTrends = Sale::selectRaw('EXTRACT(YEAR FROM created_at) as year, EXTRACT(MONTH FROM created_at) as month, SUM(comms_in_total) as revenue')
            ->where('created_at', '>=', Carbon::now()->subMonths(12))
            ->groupBy(DB::raw('EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => Carbon::createFromDate($item->year, $item->month, 1)->format('M Y'),
                    'revenue' => (float) ($item->revenue ?? 0),
                ];
            })
            ->toArray();

        return [
            'growth_percentage' => round($growthPercentage, 2),
            'revenue_trends' => $revenueTrends,
        ];
    }

    private function getPipelineData(): array
    {
        $pipelineData = Project::selectRaw('stage, COUNT(*) as count, AVG((min_price + max_price) / 2) as avg_value')
            ->groupBy('stage')
            ->get()
            ->map(function ($item) {
                return [
                    'stage' => $item->stage ?? 'Unknown',
                    'count' => $item->count,
                    'average_value' => round((float) ($item->avg_value ?? 0), 2),
                ];
            })
            ->toArray();

        return $pipelineData;
    }

    private function getProjectDistribution(): array
    {
        // Distribution by project type
        $typeDistribution = Project::join('projecttypes', 'projects.projecttype_id', '=', 'projecttypes.id')
            ->selectRaw('projecttypes.title as type, COUNT(*) as count')
            ->groupBy('projecttypes.title')
            ->get()
            ->toArray();

        // Distribution by state - simplified for now
        $stateDistribution = [
            ['state' => 'Queensland', 'count' => Project::where('estate', 'LIKE', '%QLD%')->count()],
            ['state' => 'New South Wales', 'count' => Project::where('estate', 'LIKE', '%NSW%')->count()],
            ['state' => 'Victoria', 'count' => Project::where('estate', 'LIKE', '%VIC%')->count()],
            ['state' => 'Others', 'count' => Project::whereNotLike('estate', '%QLD%')->whereNotLike('estate', '%NSW%')->whereNotLike('estate', '%VIC%')->count()],
        ];

        return [
            'by_type' => $typeDistribution,
            'by_state' => $stateDistribution,
        ];
    }

    private function getLeadActivity(): array
    {
        // Get daily enquiries for the last 30 days
        $leadActivity = PropertyEnquiry::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'enquiries' => $item->count,
                ];
            })
            ->toArray();

        return $leadActivity;
    }

    private function getGeographicDistribution(): array
    {
        // Simplified geographic distribution using estate field
        $geographic = Project::selectRaw('estate as location, COUNT(*) as project_count, AVG(avg_price) as avg_price')
            ->whereNotNull('estate')
            ->where('estate', '!=', '')
            ->groupBy('estate')
            ->orderBy('project_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'state' => $item->location,
                    'suburb' => '',
                    'project_count' => $item->project_count,
                    'avg_price' => (float) ($item->avg_price ?? 0),
                ];
            })
            ->toArray();

        return $geographic;
    }

    private function getRecentActivity(): array
    {
        // Get recent projects, enquiries, and sales
        $recentProjects = Project::latest()->limit(5)->get(['id', 'title', 'stage', 'created_at']);
        $recentEnquiries = PropertyEnquiry::latest()->limit(5)->get(['id', 'created_at']);
        $recentSales = Sale::latest()->limit(5)->get(['id', 'comms_in_total', 'created_at']);

        return [
            'projects' => $recentProjects->toArray(),
            'enquiries' => $recentEnquiries->toArray(),
            'sales' => $recentSales->toArray(),
        ];
    }

    private function getTopProjects(): array
    {
        return Project::with(['developer', 'projecttype'])
            ->withCount('lots')
            ->orderBy('total_lots', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'stage', 'estate', 'total_lots', 'avg_price', 'developer_id', 'projecttype_id'])
            ->toArray();
    }
}