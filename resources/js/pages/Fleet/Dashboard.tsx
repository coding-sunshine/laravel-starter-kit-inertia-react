import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    Area,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
    AreaChart,
} from 'recharts';
import {
    AlertTriangle,
    Bell,
    Bot,
    ClipboardList,
    MapPin,
    Route,
    Truck,
    Users,
    Wrench,
} from 'lucide-react';
interface Counts {
    vehicles: number;
    drivers: number;
    driver_vehicle_assignments: number;
    routes: number;
    trips: number;
    fuel_cards: number;
    fuel_transactions: number;
    service_schedules: number;
    work_orders: number;
    defects: number;
    compliance_items: number;
    driver_working_time: number;
    tachograph_downloads: number;
    behavior_events: number;
    geofence_events: number;
    emissions_records?: number;
    carbon_targets?: number;
    sustainability_goals?: number;
    ai_analysis_results?: number;
    ai_job_runs?: number;
    insurance_policies?: number;
    incidents?: number;
    insurance_claims?: number;
    workflow_definitions?: number;
    workflow_executions?: number;
    ev_charging_sessions?: number;
    ev_battery_data?: number;
    training_courses?: number;
    training_sessions?: number;
    driver_qualifications?: number;
    training_enrollments?: number;
    cost_allocations?: number;
    alerts?: number;
    alerts_open?: number;
    compliance_due_soon?: number;
    reports?: number;
    report_executions?: number;
    alert_preferences?: number;
    api_integrations?: number;
    api_logs?: number;
    dashcam_clips?: number;
    workshop_bays?: number;
    parts_inventory?: number;
    parts_suppliers?: number;
    tyre_inventory?: number;
    vehicle_tyres?: number;
    grey_fleet_vehicles?: number;
    mileage_claims?: number;
    pool_vehicle_bookings?: number;
    contractors?: number;
    contractor_compliance?: number;
    contractor_invoices?: number;
    driver_wellness_records?: number;
    driver_coaching_plans?: number;
    vehicle_check_templates?: number;
    vehicle_checks?: number;
    risk_assessments?: number;
    vehicle_discs?: number;
    tachograph_calibrations?: number;
    safety_policy_acknowledgments?: number;
    permit_to_work?: number;
    ppe_assignments?: number;
    safety_observations?: number;
    toolbox_talks?: number;
    todays_vehicle_checks?: number;
    fines?: number;
    vehicle_leases?: number;
    vehicle_recalls?: number;
    warranty_claims?: number;
    parking_allocations?: number;
    e_lock_events?: number;
    axle_load_readings?: number;
    data_migration_runs?: number;
}
interface WorkOrderRow { id: number; work_order_number: string; title: string; status: string; vehicle?: { id: number; registration: string }; }
interface DefectRow { id: number; defect_number: string; title: string; severity: string; vehicle?: { id: number; registration: string }; }
interface ComplianceRow { id: number; title: string; expiry_date: string; status: string; entity_type: string; entity_id: number; }
interface ComplianceAtRiskRow {
    id: number;
    primary_finding: string;
    priority: string;
    risk_score: number | null;
    created_at: string;
    detailed_analysis?: { at_risk_vehicles?: unknown[]; at_risk_drivers?: unknown[] };
}
interface ChartTripsOverTimeRow { date: string; label: string; trips: number; }
interface ChartWorkOrdersByStatusRow { name: string; value: number; }
interface ChartWorkOrdersOverTimeRow { date: string; label: string; work_orders: number; }

interface Props {
    counts: Counts;
    chartTripsOverTime: ChartTripsOverTimeRow[];
    chartWorkOrdersByStatus: ChartWorkOrdersByStatusRow[];
    chartWorkOrdersOverTime: ChartWorkOrdersOverTimeRow[];
    recentWorkOrders: WorkOrderRow[];
    recentDefects: DefectRow[];
    expiringCompliance: ComplianceRow[];
    complianceAtRisk?: ComplianceAtRiskRow | null;
    aiJobRunsUrl?: string;
    insights?: string[];
}

const CHART_COLORS = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];
const CHART_COLORS_FALLBACK = ['#0ea5e9', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444'];

const tooltipContentStyle = {
    backgroundColor: 'hsl(var(--card))',
    border: '1px solid hsl(var(--border))',
    borderRadius: '8px',
    fontSize: '12px',
    boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
};

export default function FleetDashboard({
    counts,
    chartTripsOverTime = [],
    chartWorkOrdersByStatus = [],
    chartWorkOrdersOverTime = [],
    recentWorkOrders,
    recentDefects,
    expiringCompliance,
    complianceAtRisk,
    aiJobRunsUrl,
    insights = [],
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
    ];

    const overviewBarData = [
        { name: 'Vehicles', value: counts.vehicles },
        { name: 'Drivers', value: counts.drivers },
        { name: 'Routes', value: counts.routes },
        { name: 'Trips', value: counts.trips },
        { name: 'Work orders', value: counts.work_orders },
    ];
    const hasWorkOrdersByStatus = chartWorkOrdersByStatus.some((r) => r.value > 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-8 rounded-xl p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
                            Fleet dashboard
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Overview and quick access to all fleet areas.
                        </p>
                    </div>
                    <Button asChild size="sm" className="shrink-0 gap-2">
                        <Link href="/fleet/assistant" prefetch="click">
                            <Bot className="size-4" />
                            Open Assistant
                        </Link>
                    </Button>
                </div>

                {insights.length > 0 && (
                    <Card className="border-primary/20 bg-primary/5">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Bot className="size-4 text-primary" />
                                AI insights
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm text-muted-foreground">
                            <ul className="list-inside list-disc space-y-0.5">
                                {insights.map((line, i) => (
                                    <li key={i}>{line}</li>
                                ))}
                            </ul>
                            <Button asChild size="sm" variant="outline" className="mt-2">
                                <Link href="/fleet/assistant" prefetch="click">
                                    Ask assistant
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {/* KPI strip — 4–6 cards with links (optional filters) */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-4">
                    <Link href="/fleet/vehicles" className="rounded-xl border border-border bg-card p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Truck className="size-5" />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Vehicles</p>
                                <p className="text-2xl font-bold tabular-nums text-foreground">{counts.vehicles}</p>
                            </div>
                        </div>
                    </Link>
                    <Link href="/fleet/drivers" className="rounded-xl border border-border bg-card p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Users className="size-5" />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Drivers</p>
                                <p className="text-2xl font-bold tabular-nums text-foreground">{counts.drivers}</p>
                            </div>
                        </div>
                    </Link>
                    <Link href="/fleet/trips" className="rounded-xl border border-border bg-card p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <MapPin className="size-5" />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Trips</p>
                                <p className="text-2xl font-bold tabular-nums text-foreground">{counts.trips}</p>
                            </div>
                        </div>
                    </Link>
                    <Link href="/fleet/work-orders" className="rounded-xl border border-border bg-card p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <ClipboardList className="size-5" />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Work orders</p>
                                <p className="text-2xl font-bold tabular-nums text-foreground">{counts.work_orders}</p>
                            </div>
                        </div>
                    </Link>
                    <Link href="/fleet/alerts?status=active" className="rounded-xl border border-border bg-card p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-destructive/10 text-destructive">
                                <Bell className="size-5" />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Alerts open</p>
                                <p className="text-2xl font-bold tabular-nums text-foreground">{counts.alerts_open ?? counts.alerts ?? 0}</p>
                            </div>
                        </div>
                    </Link>
                    <Link href="/fleet/compliance-items?status=expiring_soon" className="rounded-xl border border-border bg-card p-4 shadow-sm transition-all hover:border-primary/30 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <div className="flex items-center gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                                <AlertTriangle className="size-5" />
                            </div>
                            <div className="min-w-0">
                                <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Compliance due soon</p>
                                <p className="text-2xl font-bold tabular-nums text-foreground">{counts.compliance_due_soon ?? 0}</p>
                            </div>
                        </div>
                    </Link>
                </div>

                {/* Charts grid */}
                <section>
                    <h2 className="mb-4 text-lg font-semibold text-foreground">Trends & analytics</h2>
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Trips over time */}
                        <Card className="border border-border">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                    Trips (last 14 days)
                                    <Link href="/fleet/trips" className="text-sm font-normal text-primary hover:underline">View all</Link>
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">Daily trip count</p>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[240px] w-full">
                                    {chartTripsOverTime.length === 0 ? (
                                        <div className="flex h-full items-center justify-center rounded-lg bg-muted/30 text-sm text-muted-foreground">
                                            No trip data for this period.
                                        </div>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={240}>
                                            <AreaChart data={chartTripsOverTime} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                                                <defs>
                                                    <linearGradient id="tripsGradient" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stopColor="hsl(var(--primary))" stopOpacity={0.4} />
                                                        <stop offset="100%" stopColor="hsl(var(--primary))" stopOpacity={0} />
                                                    </linearGradient>
                                                </defs>
                                                <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" vertical={false} />
                                                <XAxis dataKey="label" tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} />
                                                <YAxis tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} allowDecimals={false} />
                                                <Tooltip contentStyle={tooltipContentStyle} formatter={(value: number | undefined) => [value ?? 0, 'Trips']} labelFormatter={(l) => `Date: ${l}`} />
                                                <Area type="monotone" dataKey="trips" name="Trips" stroke="hsl(var(--primary))" fill="url(#tripsGradient)" strokeWidth={2} />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Work orders over time */}
                        <Card className="border border-border">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                    Work orders created (last 14 days)
                                    <Link href="/fleet/work-orders" className="text-sm font-normal text-primary hover:underline">View all</Link>
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">New work orders per day</p>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[240px] w-full">
                                    {chartWorkOrdersOverTime.length === 0 ? (
                                        <div className="flex h-full items-center justify-center rounded-lg bg-muted/30 text-sm text-muted-foreground">
                                            No data for this period.
                                        </div>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={240}>
                                            <BarChart data={chartWorkOrdersOverTime} margin={{ top: 8, right: 8, left: 0, bottom: 0 }}>
                                                <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" vertical={false} />
                                                <XAxis dataKey="label" tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} />
                                                <YAxis tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} allowDecimals={false} />
                                                <Tooltip contentStyle={tooltipContentStyle} formatter={(value: number | undefined) => [value ?? 0, 'Work orders']} labelFormatter={(l) => `Date: ${l}`} />
                                                <Bar dataKey="work_orders" name="Work orders" fill="hsl(var(--primary))" radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Overview by category — click bar to filter list */}
                        <Card className="border border-border">
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base font-semibold text-foreground">Fleet overview by category</CardTitle>
                                <p className="text-xs text-muted-foreground">Counts across main entities · click bar to open list</p>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[240px] w-full">
                                    <ResponsiveContainer width="100%" height={240}>
                                        <BarChart data={overviewBarData} margin={{ top: 8, right: 8, left: 0, bottom: 0 }} layout="vertical" className="[&_.recharts-cartesian-grid-horizontal]:opacity-0">
                                            <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" horizontal={false} />
                                            <XAxis type="number" tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} allowDecimals={false} />
                                            <YAxis type="category" dataKey="name" width={80} tick={{ fill: 'hsl(var(--muted-foreground))', fontSize: 11 }} />
                                            <Tooltip contentStyle={tooltipContentStyle} formatter={(value: number | undefined) => [value ?? 0, 'Count']} />
                                            <Bar
                                                dataKey="value"
                                                name="Count"
                                                fill="hsl(var(--primary))"
                                                radius={[0, 4, 4, 0]}
                                                cursor="pointer"
                                                onClick={(data: { name?: string }) => {
                                                    const name = data?.name;
                                                    const url =
                                                        name === 'Vehicles' ? '/fleet/vehicles'
                                                        : name === 'Drivers' ? '/fleet/drivers'
                                                        : name === 'Routes' ? '/fleet/routes'
                                                        : name === 'Trips' ? '/fleet/trips'
                                                        : name === 'Work orders' ? '/fleet/work-orders'
                                                        : null;
                                                    if (url) window.location.href = url;
                                                }}
                                            />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Work orders by status — click segment to list with filter */}
                        <Card className="border border-border">
                            <CardHeader className="pb-2">
                                <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                    Work orders by status
                                    <Link href="/fleet/work-orders" className="text-sm font-normal text-primary hover:underline">View all</Link>
                                </CardTitle>
                                <p className="text-xs text-muted-foreground">Breakdown by status · click segment to filter list</p>
                            </CardHeader>
                            <CardContent>
                                <div className="h-[240px] w-full">
                                    {!hasWorkOrdersByStatus ? (
                                        <div className="flex h-full items-center justify-center rounded-lg bg-muted/30 text-sm text-muted-foreground">
                                            No work orders yet.
                                        </div>
                                    ) : (
                                        <ResponsiveContainer width="100%" height={240}>
                                            <PieChart>
                                                <Pie
                                                    data={chartWorkOrdersByStatus}
                                                    dataKey="value"
                                                    nameKey="name"
                                                    cx="50%"
                                                    cy="50%"
                                                    innerRadius={56}
                                                    outerRadius={88}
                                                    paddingAngle={2}
                                                    label={({ name, percent }) => `${name} ${((percent ?? 0) * 100).toFixed(0)}%`}
                                                    labelLine={{ stroke: 'hsl(var(--muted-foreground))' }}
                                                    cursor="pointer"
                                                    onClick={(data: { name?: string }) => {
                                                        const name = data?.name ?? '';
                                                        const status = name.toLowerCase().replace(/\s+/g, '_');
                                                        if (status && status !== 'no_orders') window.location.href = `/fleet/work-orders?status=${status}`;
                                                        else window.location.href = '/fleet/work-orders';
                                                    }}
                                                >
                                                    {chartWorkOrdersByStatus.map((_, index) => (
                                                        <Cell key={index} fill={CHART_COLORS[index % CHART_COLORS.length] || CHART_COLORS_FALLBACK[index % CHART_COLORS_FALLBACK.length]} />
                                                    ))}
                                                </Pie>
                                                <Tooltip contentStyle={tooltipContentStyle} formatter={(value: number | undefined, name: string | undefined) => [value ?? 0, name ?? '']} />
                                                <Legend layout="horizontal" align="center" verticalAlign="bottom" wrapperStyle={{ fontSize: 11 }} />
                                            </PieChart>
                                        </ResponsiveContainer>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                {/* Activity: recent items and compliance */}
                <section>
                    <h2 className="mb-4 text-lg font-semibold text-foreground">Activity</h2>
                <div className="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
                    <Card className="border border-border">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                Recent work orders
                                <Link href="/fleet/work-orders" className="text-sm font-normal text-primary hover:underline">View all</Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentWorkOrders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No work orders yet.</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {recentWorkOrders.map((wo) => (
                                        <li key={wo.id} className="flex items-center justify-between border-b border-dashed pb-2 last:border-0">
                                            <Link href={`/fleet/work-orders/${wo.id}`} className="font-medium hover:underline">{wo.work_order_number}</Link>
                                            <span className="text-muted-foreground">{wo.vehicle?.registration ?? wo.status}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                    <Card className="border border-border">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                Recent defects
                                <Link href="/fleet/defects" className="text-sm font-normal text-primary hover:underline">View all</Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentDefects.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No defects reported.</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {recentDefects.map((d) => (
                                        <li key={d.id} className="flex items-center justify-between border-b border-dashed pb-2 last:border-0">
                                            <Link href={`/fleet/defects/${d.id}`} className="font-medium hover:underline">{d.defect_number}</Link>
                                            <span className="text-muted-foreground">{d.severity}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                                    <Card className="border border-border">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                Expiring compliance
                                <Link href="/fleet/compliance-items" className="text-sm font-normal text-primary hover:underline">View all</Link>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {expiringCompliance.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Nothing expiring soon.</p>
                            ) : (
                                <>
                                <ul className="space-y-2 text-sm">
                                    {expiringCompliance.map((c) => (
                                        <li key={c.id} className="flex items-center justify-between border-b border-dashed pb-2 last:border-0">
                                            <Link href={`/fleet/compliance-items/${c.id}`} className="font-medium hover:underline">{c.title}</Link>
                                            <span className="text-muted-foreground">{new Date(c.expiry_date).toLocaleDateString()}</span>
                                        </li>
                                    ))}
                                </ul>
                                <Link href="/fleet/assistant?prompt=What%27s%20expiring%20soon%3F" className="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-primary hover:underline">
                                    <Bot className="size-3.5" />
                                    Ask assistant
                                </Link>
                                </>
                            )}
                        </CardContent>
                    </Card>
                    <Card className="border border-border">
                        <CardHeader className="pb-2">
                            <CardTitle className="flex items-center justify-between text-base font-semibold text-foreground">
                                Compliance at risk (AI)
                                {aiJobRunsUrl && (
                                    <Link href={aiJobRunsUrl} className="text-sm font-normal text-primary hover:underline">Run prediction</Link>
                                )}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {!complianceAtRisk ? (
                                <p className="text-sm text-muted-foreground">No compliance prediction run yet. Run a job from AI job runs.</p>
                            ) : (
                                <div className="space-y-2 text-sm">
                                    <p className="font-medium">{complianceAtRisk.primary_finding}</p>
                                    <p className="text-muted-foreground">
                                        Priority: <span className="capitalize">{complianceAtRisk.priority}</span>
                                        {complianceAtRisk.detailed_analysis && (
                                            <> · {((complianceAtRisk.detailed_analysis.at_risk_vehicles?.length ?? 0) + (complianceAtRisk.detailed_analysis.at_risk_drivers?.length ?? 0))} at risk</>
                                        )}
                                    </p>
                                    <p className="text-xs text-muted-foreground">Updated {new Date(complianceAtRisk.created_at).toLocaleString()}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
                </section>

                {/* Quick links: main fleet areas */}
                <section>
                    <h2 className="mb-4 text-lg font-semibold text-foreground">Quick links</h2>
                    <div className="flex flex-wrap gap-2">
                        <Link href="/fleet/vehicles" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <Truck className="size-4" /> Vehicles
                        </Link>
                        <Link href="/fleet/drivers" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <Users className="size-4" /> Drivers
                        </Link>
                        <Link href="/fleet/trips" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <MapPin className="size-4" /> Trips
                        </Link>
                        <Link href="/fleet/routes" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <Route className="size-4" /> Routes
                        </Link>
                        <Link href="/fleet/work-orders" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <ClipboardList className="size-4" /> Work orders
                        </Link>
                        <Link href="/fleet/defects" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <AlertTriangle className="size-4" /> Defects
                        </Link>
                        <Link href="/fleet/assistant" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <Bot className="size-4" /> Assistant
                        </Link>
                        <Link href="/fleet/alerts" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <Bell className="size-4" /> Alerts
                        </Link>
                        <Link href="/fleet/locations" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <MapPin className="size-4" /> Locations
                        </Link>
                        <Link href="/fleet/garages" className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium text-foreground transition-colors hover:border-primary/30 hover:bg-muted hover:text-primary">
                            <Wrench className="size-4" /> Garages
                        </Link>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
