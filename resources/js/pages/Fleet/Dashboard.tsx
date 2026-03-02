import AppLayout from '@/layouts/app-layout';
import {
    FleetBlockSectionHeader,
    FleetDataCard,
    fleetDataCardListClass,
    fleetDataCardRowClass,
    fleetDataCardRowPrimaryClass,
    fleetDataCardRowSecondaryClass,
    FleetGlassCard,
    FleetPageShell,
} from '@/components/fleet';
import { Button } from '@/components/ui/button';
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
import { FleetMap, FleetMapMarker } from '@/components/fleet/FleetMap';
import { useMemo } from 'react';
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

interface MapVehicleRow {
    id: number;
    registration: string;
    lat: number;
    lng: number;
    source: 'current' | 'home';
}

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
    mapVehicles?: MapVehicleRow[];
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

const DEFAULT_MAP_CENTER = { lat: 51.5, lng: -0.1 };

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
    mapVehicles = [],
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

    const mapCenter = useMemo(() => {
        if (mapVehicles.length === 0) return DEFAULT_MAP_CENTER;
        const sum = mapVehicles.reduce((a, v) => ({ lat: a.lat + v.lat, lng: a.lng + v.lng }), { lat: 0, lng: 0 });
        return { lat: sum.lat / mapVehicles.length, lng: sum.lng / mapVehicles.length };
    }, [mapVehicles]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Dashboard" />
            <FleetPageShell
                title="Fleet dashboard"
                subtitle="Overview and quick access to all fleet areas."
                rightActions={
                    <Button asChild size="sm" className="shrink-0 gap-2">
                        <Link href="/fleet/assistant" prefetch="click">
                            <Bot className="size-4" />
                            Open Assistant
                        </Link>
                    </Button>
                }
                className="flex flex-1 flex-col gap-8 p-4 md:p-6"
                contentWrapperClassName="flex flex-1 flex-col gap-8"
            >
                {insights.length > 0 && (
                    <FleetGlassCard className="border-l-4 border-l-primary/70">
                        <div className="space-y-1 text-sm text-muted-foreground">
                            <h3 className="flex items-center gap-2 text-base font-semibold text-foreground">
                                <Bot className="size-4 text-primary" />
                                AI insights
                            </h3>
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
                        </div>
                    </FleetGlassCard>
                )}

                <FleetBlockSectionHeader>Critical metrics</FleetBlockSectionHeader>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 lg:gap-4">
                    <Link href="/fleet/vehicles" className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <FleetGlassCard className="border-l-4 border-l-primary/70 p-4 transition-all hover:shadow-md">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Truck className="size-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Vehicles</p>
                                    <p className="text-2xl font-bold tabular-nums text-foreground">{counts.vehicles}</p>
                                </div>
                            </div>
                        </FleetGlassCard>
                    </Link>
                    <Link href="/fleet/drivers" className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <FleetGlassCard className="border-l-4 border-l-violet-400/70 p-4 transition-all hover:shadow-md">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600">
                                    <Users className="size-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Drivers</p>
                                    <p className="text-2xl font-bold tabular-nums text-foreground">{counts.drivers}</p>
                                </div>
                            </div>
                        </FleetGlassCard>
                    </Link>
                    <Link href="/fleet/trips" className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <FleetGlassCard className="border-l-4 border-l-emerald-400/70 p-4 transition-all hover:shadow-md">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                                    <MapPin className="size-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Trips</p>
                                    <p className="text-2xl font-bold tabular-nums text-foreground">{counts.trips}</p>
                                </div>
                            </div>
                        </FleetGlassCard>
                    </Link>
                    <Link href="/fleet/work-orders" className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <FleetGlassCard className="border-l-4 border-l-sky-400/70 p-4 transition-all hover:shadow-md">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600">
                                    <ClipboardList className="size-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Work orders</p>
                                    <p className="text-2xl font-bold tabular-nums text-foreground">{counts.work_orders}</p>
                                </div>
                            </div>
                        </FleetGlassCard>
                    </Link>
                    <Link href="/fleet/alerts?status=active" className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <FleetGlassCard className="border-l-4 border-l-rose-400/70 p-4 transition-all hover:shadow-md">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600">
                                    <Bell className="size-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Alerts open</p>
                                    <p className="text-2xl font-bold tabular-nums text-foreground">{counts.alerts_open ?? counts.alerts ?? 0}</p>
                                </div>
                            </div>
                        </FleetGlassCard>
                    </Link>
                    <Link href="/fleet/compliance-items?status=expiring_soon" className="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <FleetGlassCard className="border-l-4 border-l-amber-400/70 p-4 transition-all hover:shadow-md">
                            <div className="flex items-center gap-3">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600">
                                    <AlertTriangle className="size-5" />
                                </div>
                                <div className="min-w-0">
                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Compliance due soon</p>
                                    <p className="text-2xl font-bold tabular-nums text-foreground">{counts.compliance_due_soon ?? 0}</p>
                                </div>
                            </div>
                        </FleetGlassCard>
                    </Link>
                </div>

                {/* Fleet map — glass card (reference UI) */}
                <FleetGlassCard className="overflow-hidden p-0">
                    <div className="border-b border-white/30 px-4 pb-2 pt-4">
                        <h3 className="flex items-center gap-2 text-base font-semibold text-foreground">
                            <MapPin className="size-4 text-primary" />
                            Fleet map
                        </h3>
                        <p className="text-xs text-muted-foreground">
                            {mapVehicles.length > 0
                                ? `${mapVehicles.length} vehicle(s) with location — current position or home base`
                                : 'Add vehicle locations or set home location to see the fleet on the map.'}
                        </p>
                    </div>
                    <div className="p-0">
                        <div className="relative">
                            <FleetMap
                                center={mapCenter}
                                zoom={mapVehicles.length >= 2 ? 8 : 10}
                                mapContainerStyle={{ width: '100%', height: '340px' }}
                                className="rounded-b-lg"
                            >
                                {mapVehicles.map((v) => (
                                    <FleetMapMarker
                                        key={v.id}
                                        position={{ lat: v.lat, lng: v.lng }}
                                        title={`${v.registration}${v.source === 'home' ? ' (home)' : ''}`}
                                        label={v.registration.slice(0, 2).toUpperCase()}
                                    />
                                ))}
                            </FleetMap>
                            {mapVehicles.length === 0 && (
                                <div className="pointer-events-none absolute inset-0 flex items-center justify-center rounded-b-lg bg-muted/40">
                                    <p className="rounded-lg border border-border bg-card/95 px-4 py-3 text-center text-sm text-muted-foreground shadow-sm">
                                        No vehicle locations yet. Set current position or home location on vehicles to see them here.
                                    </p>
                                </div>
                            )}
                        </div>
                        <div className="flex justify-end border-t border-white/30 bg-white/20 px-4 py-2">
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/fleet/vehicles" prefetch="click">
                                    Manage vehicles
                                </Link>
                            </Button>
                        </div>
                    </div>
                </FleetGlassCard>

                <section>
                    <FleetBlockSectionHeader>Trends &amp; analytics</FleetBlockSectionHeader>
                    <div className="grid gap-6 lg:grid-cols-2">
                        <FleetGlassCard>
                            <div className="pb-2">
                                <div className="flex items-center justify-between text-base font-semibold text-foreground">
                                    Trips (last 14 days)
                                    <Link href="/fleet/trips" className="text-sm font-normal text-primary hover:underline">View all</Link>
                                </div>
                                <p className="text-xs text-muted-foreground">Daily trip count</p>
                            </div>
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
                        </FleetGlassCard>

                        <FleetGlassCard>
                            <div className="pb-2">
                                <div className="flex items-center justify-between text-base font-semibold text-foreground">
                                    Work orders created (last 14 days)
                                    <Link href="/fleet/work-orders" className="text-sm font-normal text-primary hover:underline">View all</Link>
                                </div>
                                <p className="text-xs text-muted-foreground">New work orders per day</p>
                            </div>
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
                        </FleetGlassCard>

                        <FleetGlassCard>
                            <div className="pb-2">
                                <h3 className="text-base font-semibold text-foreground">Fleet overview by category</h3>
                                <p className="text-xs text-muted-foreground">Counts across main entities · click bar to open list</p>
                            </div>
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
                        </FleetGlassCard>

                        <FleetGlassCard>
                            <div className="pb-2">
                                <div className="flex items-center justify-between text-base font-semibold text-foreground">
                                    Work orders by status
                                    <Link href="/fleet/work-orders" className="text-sm font-normal text-primary hover:underline">View all</Link>
                                </div>
                                <p className="text-xs text-muted-foreground">Breakdown by status · click segment to filter list</p>
                            </div>
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
                        </FleetGlassCard>
                    </div>
                </section>

                <section>
                    <FleetBlockSectionHeader>Activity</FleetBlockSectionHeader>
                    <div className="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
                        <FleetDataCard
                            title="Recent work orders"
                            right={<Link href="/fleet/work-orders" className="text-sm font-normal text-primary hover:underline">View all</Link>}
                        >
                            {recentWorkOrders.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No work orders yet.</p>
                            ) : (
                                <ul className={fleetDataCardListClass}>
                                    {recentWorkOrders.map((wo) => (
                                        <li key={wo.id} className={fleetDataCardRowClass}>
                                            <Link href={`/fleet/work-orders/${wo.id}`} className={fleetDataCardRowPrimaryClass}>
                                                {wo.work_order_number}
                                            </Link>
                                            <span className={fleetDataCardRowSecondaryClass}>{wo.vehicle?.registration ?? wo.status}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </FleetDataCard>
                        <FleetDataCard
                            title="Recent defects"
                            right={<Link href="/fleet/defects" className="text-sm font-normal text-primary hover:underline">View all</Link>}
                        >
                            {recentDefects.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No defects reported.</p>
                            ) : (
                                <ul className={fleetDataCardListClass}>
                                    {recentDefects.map((d) => (
                                        <li key={d.id} className={fleetDataCardRowClass}>
                                            <Link href={`/fleet/defects/${d.id}`} className={fleetDataCardRowPrimaryClass}>
                                                {d.defect_number}
                                            </Link>
                                            <span className={fleetDataCardRowSecondaryClass}>{d.severity}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </FleetDataCard>
                        <FleetDataCard
                            title="Expiring compliance"
                            right={<Link href="/fleet/compliance-items" className="text-sm font-normal text-primary hover:underline">View all</Link>}
                        >
                            {expiringCompliance.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Nothing expiring soon.</p>
                            ) : (
                                <>
                                    <ul className={fleetDataCardListClass}>
                                        {expiringCompliance.map((c) => (
                                            <li key={c.id} className={fleetDataCardRowClass}>
                                                <Link href={`/fleet/compliance-items/${c.id}`} className={fleetDataCardRowPrimaryClass}>
                                                    {c.title}
                                                </Link>
                                                <span className={fleetDataCardRowSecondaryClass}>{new Date(c.expiry_date).toLocaleDateString()}</span>
                                            </li>
                                        ))}
                                    </ul>
                                    <Link href="/fleet/assistant?prompt=What%27s%20expiring%20soon%3F" className="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-primary hover:underline">
                                        <Bot className="size-3.5" />
                                        Ask assistant
                                    </Link>
                                </>
                            )}
                        </FleetDataCard>
                        <FleetDataCard
                            title="Compliance at risk (AI)"
                            right={aiJobRunsUrl ? <Link href={aiJobRunsUrl} className="text-sm font-normal text-primary hover:underline">Run prediction</Link> : undefined}
                        >
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
                        </FleetDataCard>
                    </div>
                </section>

                <section>
                    <FleetBlockSectionHeader>Quick links</FleetBlockSectionHeader>
                    <FleetGlassCard>
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
                    </FleetGlassCard>
                </section>
            </FleetPageShell>
        </AppLayout>
    );
}
