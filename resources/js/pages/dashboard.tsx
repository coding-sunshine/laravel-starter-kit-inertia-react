import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { KpiCard } from '@/components/ui/kpi-card';
import { PipelineChart } from '@/components/charts/pipeline-chart';
import { RevenueChart } from '@/components/charts/revenue-chart';
import { DonutChart } from '@/components/charts/donut-chart';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import {
    Building2,
    DollarSign,
    TrendingUp,
    Users,
    MapPin,
    Calendar,
    FileText,
    Activity,
    BarChart3,
    Target,
    Briefcase,
    Home,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardMetrics {
    kpis: {
        total_projects: number;
        total_lots: number;
        total_contacts: number;
        total_sales: number;
        total_revenue: number;
        average_deal_value: number;
        pipeline_value: number;
        conversion_rate: number;
        monthly_growth: number;
    };
    charts: {
        pipeline: Array<{
            stage: string;
            count: number;
            average_value: number;
        }>;
        revenue_trends: Array<{
            period: string;
            revenue: number;
        }>;
        project_distribution: {
            by_type: Array<{ type: string; count: number }>;
            by_state: Array<{ state: string; count: number }>;
        };
        lead_activity: Array<{
            date: string;
            enquiries: number;
        }>;
        geographic_distribution: Array<{
            state: string;
            suburb: string;
            project_count: number;
            avg_price: number;
        }>;
    };
    recent_activity: {
        projects: Array<{
            id: number;
            title: string;
            stage: string;
            created_at: string;
        }>;
        enquiries: Array<any>;
        sales: Array<any>;
    };
    top_projects: Array<{
        id: number;
        title: string;
        stage: string;
        estate: string;
        total_lots: number;
        avg_price: number;
    }>;
}

export default function Dashboard() {
    const { auth } = usePage<SharedData>().props;
    const [metrics, setMetrics] = useState<DashboardMetrics | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetchDashboardMetrics();
    }, []);

    const fetchDashboardMetrics = async () => {
        try {
            setLoading(true);
            const response = await fetch('/dashboard/metrics', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch dashboard metrics');
            }

            const data = await response.json();
            setMetrics(data);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'An error occurred');
            console.error('Error fetching dashboard metrics:', err);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard" />
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                    <div className="flex items-center justify-center h-64">
                        <div className="text-center">
                            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                            <p className="mt-4 text-muted-foreground">Loading dashboard...</p>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard" />
                <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6">
                    <div className="fusion-card p-6 text-center">
                        <p className="text-red-600 dark:text-red-400">Error: {error}</p>
                        <Button onClick={fetchDashboardMetrics} className="mt-4">
                            Retry
                        </Button>
                    </div>
                </div>
            </AppLayout>
        );
    }

    if (!metrics) {
        return null;
    }

    const { kpis, charts, recent_activity, top_projects } = metrics;

    // Prepare chart data
    const distributionByType = charts.project_distribution.by_type.map(item => ({
        name: item.type,
        value: item.count,
    }));

    const distributionByState = charts.project_distribution.by_state.map(item => ({
        name: item.state,
        value: item.count,
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-6 bg-gray-50 dark:bg-gray-900">
                {/* Header */}
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold text-foreground">
                            Welcome back, {auth.user.name}
                        </h1>
                        <p className="text-muted-foreground mt-1">
                            Here's what's happening with your properties today
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm">
                            <Calendar className="w-4 h-4 mr-2" />
                            Last 30 days
                        </Button>
                        <Button variant="outline" size="sm">
                            <FileText className="w-4 h-4 mr-2" />
                            Export Report
                        </Button>
                    </div>
                </div>

                {/* KPI Cards */}
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <KpiCard
                        title="Total Projects"
                        value={kpis.total_projects}
                        icon={Building2}
                        subtitle="Active development projects"
                    />
                    <KpiCard
                        title="Total Lots"
                        value={kpis.total_lots}
                        icon={Home}
                        subtitle="Available for sale"
                    />
                    <KpiCard
                        title="Total Revenue"
                        value={`$${(kpis.total_revenue / 1000000).toFixed(1)}M`}
                        icon={DollarSign}
                        change={{
                            value: kpis.monthly_growth,
                            isPositive: kpis.monthly_growth > 0,
                            period: 'last month'
                        }}
                    />
                    <KpiCard
                        title="Pipeline Value"
                        value={`$${(kpis.pipeline_value / 1000000).toFixed(1)}M`}
                        icon={Target}
                        subtitle="Potential revenue"
                    />
                    <KpiCard
                        title="Total Contacts"
                        value={kpis.total_contacts}
                        icon={Users}
                        subtitle="Leads and customers"
                    />
                    <KpiCard
                        title="Avg Deal Value"
                        value={`$${Math.round(kpis.average_deal_value / 1000)}K`}
                        icon={Briefcase}
                        subtitle="Per transaction"
                    />
                    <KpiCard
                        title="Conversion Rate"
                        value={`${kpis.conversion_rate}%`}
                        icon={TrendingUp}
                        subtitle="Leads to sales"
                    />
                    <KpiCard
                        title="Total Sales"
                        value={kpis.total_sales}
                        icon={Activity}
                        subtitle="Completed transactions"
                    />
                </div>

                {/* Charts Row 1 */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Sales Pipeline */}
                    <div className="fusion-card p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-lg font-semibold text-foreground">
                                    Sales Pipeline
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Projects by development stage
                                </p>
                            </div>
                            <BarChart3 className="w-5 h-5 text-muted-foreground" />
                        </div>
                        <PipelineChart data={charts.pipeline} className="h-80" />
                    </div>

                    {/* Revenue Trends */}
                    <div className="fusion-card p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-lg font-semibold text-foreground">
                                    Revenue Trends
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Sales performance over time
                                </p>
                            </div>
                            <TrendingUp className="w-5 h-5 text-muted-foreground" />
                        </div>
                        <RevenueChart data={charts.revenue_trends} className="h-80" />
                    </div>
                </div>

                {/* Charts Row 2 */}
                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Project Distribution by Type */}
                    <div className="fusion-card p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-lg font-semibold text-foreground">
                                    By Property Type
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Project distribution
                                </p>
                            </div>
                            <Building2 className="w-5 h-5 text-muted-foreground" />
                        </div>
                        <DonutChart data={distributionByType} className="h-64" />
                    </div>

                    {/* Project Distribution by State */}
                    <div className="fusion-card p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-lg font-semibold text-foreground">
                                    By Location
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Geographic distribution
                                </p>
                            </div>
                            <MapPin className="w-5 h-5 text-muted-foreground" />
                        </div>
                        <DonutChart data={distributionByState} className="h-64" />
                    </div>

                    {/* Top Projects */}
                    <div className="fusion-card p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h3 className="text-lg font-semibold text-foreground">
                                    Top Projects
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    By lot count
                                </p>
                            </div>
                            <Building2 className="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div className="space-y-3 max-h-64 overflow-y-auto">
                            {top_projects.slice(0, 8).map((project) => (
                                <div
                                    key={project.id}
                                    className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                >
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-foreground truncate">
                                            {project.title}
                                        </p>
                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                            <span>{project.stage}</span>
                                            <span>•</span>
                                            <span>{project.estate}</span>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-semibold text-foreground">
                                            {project.total_lots} lots
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            ${Math.round((project.avg_price || 0) / 1000)}K avg
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Recent Activity */}
                <div className="fusion-card p-6">
                    <div className="flex items-center justify-between mb-4">
                        <div>
                            <h3 className="text-lg font-semibold text-foreground">
                                Recent Activity
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                Latest projects and updates
                            </p>
                        </div>
                        <Activity className="w-5 h-5 text-muted-foreground" />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <h4 className="text-sm font-medium text-foreground mb-2">
                                Recent Projects
                            </h4>
                            <div className="space-y-2">
                                {recent_activity.projects.slice(0, 5).map((project) => (
                                    <div
                                        key={project.id}
                                        className="text-sm p-2 bg-gray-50 dark:bg-gray-800 rounded"
                                    >
                                        <p className="font-medium truncate">{project.title}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {project.stage} • {new Date(project.created_at).toLocaleDateString()}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                        <div>
                            <h4 className="text-sm font-medium text-foreground mb-2">
                                Recent Enquiries
                            </h4>
                            <div className="text-sm text-muted-foreground">
                                {recent_activity.enquiries.length > 0
                                    ? `${recent_activity.enquiries.length} new enquiries`
                                    : 'No recent enquiries'
                                }
                            </div>
                        </div>
                        <div>
                            <h4 className="text-sm font-medium text-foreground mb-2">
                                Recent Sales
                            </h4>
                            <div className="text-sm text-muted-foreground">
                                {recent_activity.sales.length > 0
                                    ? `${recent_activity.sales.length} recent sales`
                                    : 'No recent sales'
                                }
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}