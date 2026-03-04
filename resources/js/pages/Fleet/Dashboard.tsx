import {
    FleetAiPanel,
    FleetChartCard,
    FleetHealthBanner,
    FleetKpiCard,
    FleetPageShell,
} from '@/components/fleet';
import { FleetDashboardSkeleton } from '@/components/fleet/fleet-dashboard-skeleton';
import {
    FleetMap,
    FleetMapInfoWindow,
    FleetMapPolygon,
    FleetMapPolyline,
} from '@/components/fleet/FleetMap';
import { FleetMapClusterer } from '@/components/fleet/FleetMapClusterer';
import {
    StaggerItem,
    StaggerList,
} from '@/components/motion/stagger-list';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    Bell,
    Calendar,
    ChevronDown,
    ChevronUp,
    MapPinned,
    Truck,
    Users,
    Wrench,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import {
    Area,
    AreaChart,
    CartesianGrid,
    Cell,
    Line,
    LineChart,
    Pie,
    PieChart,
    XAxis,
    YAxis,
} from 'recharts';

/* ------------------------------------------------------------------ */
/*  Types                                                              */
/* ------------------------------------------------------------------ */

interface MapVehicleRow {
    id: number;
    registration: string;
    lat: number;
    lng: number;
    source: 'current' | 'home';
}

interface ChartData {
    kpiTrends: Record<
        string,
        {
            current: number;
            previous: number;
            change: number;
            direction: string;
        }
    >;
    kpiSparklines: Record<string, number[]>;
    chartFleetActivity: {
        date: string;
        label: string;
        trips: number;
        work_orders: number;
    }[];
    chartCostBreakdown: { name: string; value: number }[];
    chartFuelCostTrend: { date: string; label: string; cost: number }[];
    chartDriverSafetyDistribution: { name: string; value: number }[];
    aiPredictions: {
        id: number;
        priority: string;
        primary_finding: string;
        analysis_type: string;
        entity_type: string;
        entity_id: number;
    }[];
    upcomingMaintenance: {
        id: number;
        service_type: string;
        next_service_due_date: string;
        vehicle_name: string;
    }[];
}

interface Props {
    counts: {
        vehicles: number;
        drivers: number;
        work_orders: number;
        alerts_open?: number;
        alerts?: number;
        [key: string]: number | undefined;
    };
    fleet_ai_summary?: string | null;
    fleet_health_score?: number | null;
    fleet_health_breakdown?: {
        compliance_pct: number;
        compliance_label: string;
        open_alerts: number;
        open_defects: number;
        overdue_work_orders: number;
        vehicles: number;
    } | null;
    chartData?: ChartData;
    mapVehicles?: MapVehicleRow[];
    mapGeofences?: {
        id: number;
        name: string;
        paths: { lat: number; lng: number }[];
    }[];
    mapPolylines?: {
        trip_id: number;
        path: { lat: number; lng: number }[];
    }[];
    expiringCompliance?: {
        id: number;
        title: string;
        expiry_date: string;
        status: string;
    }[];
    recentWorkOrders?: {
        id: number;
        work_order_number: string;
        title: string;
        status: string;
        vehicle?: { id: number; registration: string };
    }[];
    [key: string]: unknown;
}

/* ------------------------------------------------------------------ */
/*  Chart configs                                                      */
/* ------------------------------------------------------------------ */

const CHART_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

const fleetActivityConfig = {
    trips: { label: 'Trips', color: 'var(--chart-1)' },
    work_orders: { label: 'Work Orders', color: 'var(--chart-2)' },
} satisfies ChartConfig;

const costBreakdownConfig = {
    value: { label: 'Cost' },
    Fuel: { label: 'Fuel', color: 'var(--chart-1)' },
    Maintenance: { label: 'Maintenance', color: 'var(--chart-2)' },
    Insurance: { label: 'Insurance', color: 'var(--chart-3)' },
} satisfies ChartConfig;

const fuelTrendConfig = {
    cost: { label: 'Fuel Spend', color: 'var(--chart-3)' },
} satisfies ChartConfig;

const safetyConfig = {
    value: { label: 'Drivers' },
    'Excellent (90+)': { label: 'Excellent (90+)', color: 'var(--chart-1)' },
    'Good (70-89)': { label: 'Good (70-89)', color: 'var(--chart-4)' },
    'Needs Attention (<70)': {
        label: 'Needs Attention (<70)',
        color: 'var(--chart-5)',
    },
} satisfies ChartConfig;

const DEFAULT_MAP_CENTER = { lat: 51.5, lng: -0.1 };


/* ------------------------------------------------------------------ */
/*  Component                                                          */
/* ------------------------------------------------------------------ */

export default function FleetDashboard({
    counts,
    fleet_ai_summary,
    fleet_health_score,
    fleet_health_breakdown,
    chartData,
    mapVehicles = [],
    mapGeofences = [],
    mapPolylines = [],
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
    ];

    /* -- Chart data (from deferred prop) -- */
    const kpiTrends = chartData?.kpiTrends;
    const kpiSparklines = chartData?.kpiSparklines;
    const fleetActivity = chartData?.chartFleetActivity ?? [];
    const costBreakdown = chartData?.chartCostBreakdown ?? [];
    const fuelCostTrend = chartData?.chartFuelCostTrend ?? [];
    const driverSafety = chartData?.chartDriverSafetyDistribution ?? [];
    const aiPredictions = chartData?.aiPredictions ?? [];
    const upcomingMaintenance = chartData?.upcomingMaintenance ?? [];

    /* -- Overdue work orders (from breakdown or default) -- */
    const overdueWorkOrders = fleet_health_breakdown?.overdue_work_orders ?? 0;

    /* -- Recent alerts from chartData (reuse active alerts from breakdown) -- */
    const activeAlerts = fleet_health_breakdown?.open_alerts ?? 0;

    /* -- Map state -- */
    const [liveMapVehicles, setLiveMapVehicles] =
        useState<MapVehicleRow[]>(mapVehicles);
    const [positionsUpdatedAt, setPositionsUpdatedAt] = useState<string | null>(
        null,
    );
    const [selectedVehicleId, setSelectedVehicleId] = useState<number | null>(
        null,
    );
    const [mapInstance, setMapInstance] = useState<google.maps.Map | null>(
        null,
    );
    const isMobile = useIsMobile();
    const selectedVehicle = useMemo(
        () => liveMapVehicles.find((v) => v.id === selectedVehicleId) ?? null,
        [liveMapVehicles, selectedVehicleId],
    );
    const [mapOpen, setMapOpen] = useState(false);

    useEffect(() => {
        // eslint-disable-next-line @eslint-react/hooks-extra/no-direct-set-state-in-use-effect -- sync server prop to local state for live updates
        setLiveMapVehicles(mapVehicles);
    }, [mapVehicles]);

    useEffect(() => {
        if (mapVehicles.length === 0) return;
        const fetchPositions = () => {
            fetch('/fleet/dashboard/positions', {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((res) => res.json())
                .then(
                    (data: {
                        vehicles: MapVehicleRow[];
                        updated_at: string;
                    }) => {
                        setLiveMapVehicles(data.vehicles);
                        setPositionsUpdatedAt(data.updated_at);
                    },
                )
                .catch(() => {
                    /* ignore */
                });
        };
        fetchPositions();
        const interval = setInterval(fetchPositions, 20000);
        return () => clearInterval(interval);
    }, [mapVehicles.length]);

    const mapCenter = useMemo(() => {
        if (liveMapVehicles.length === 0) return DEFAULT_MAP_CENTER;
        const sum = liveMapVehicles.reduce(
            (a, v) => ({ lat: a.lat + v.lat, lng: a.lng + v.lng }),
            { lat: 0, lng: 0 },
        );
        return {
            lat: sum.lat / liveMapVehicles.length,
            lng: sum.lng / liveMapVehicles.length,
        };
    }, [liveMapVehicles]);

    /* -- Loading state for deferred props -- */
    const isChartDataLoading = !chartData;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Dashboard" />
            <FleetPageShell
                title="Fleet dashboard"
                subtitle="Overview of your fleet at a glance."
                className="flex flex-1 flex-col gap-6 p-4 md:p-6"
                contentWrapperClassName="flex flex-1 flex-col gap-6"
            >
                {/* ============================================= */}
                {/* Section 1 — Fleet Health Banner (full width)   */}
                {/* ============================================= */}
                {fleet_health_score != null && (
                    <FleetHealthBanner
                        score={fleet_health_score}
                        summary={fleet_ai_summary}
                        breakdown={fleet_health_breakdown}
                    />
                )}

                {/* ============================================= */}
                {/* Loading skeleton for deferred dashboard data    */}
                {/* ============================================= */}
                {isChartDataLoading ? (
                    <FleetDashboardSkeleton />
                ) : (
                    <StaggerList
                        className="flex flex-col gap-6"
                        staggerDelay={0.08}
                    >
                        {/* ============================================= */}
                        {/* Section 2 — Hero KPI Cards                     */}
                        {/* ============================================= */}
                        <StaggerItem duration={0.25}>
                        <StaggerList
                            className="grid grid-cols-2 gap-4 md:grid-cols-4"
                            staggerDelay={0.06}
                        >
                            <StaggerItem duration={0.25}>
                            <FleetKpiCard
                                title="Vehicles"
                                value={counts.vehicles}
                                trend={
                                    (kpiTrends?.vehicles?.direction as
                                        | 'up'
                                        | 'down'
                                        | 'flat') ?? undefined
                                }
                                trendValue={
                                    kpiTrends?.vehicles
                                        ? `${kpiTrends.vehicles.change >= 0 ? '+' : ''}${kpiTrends.vehicles.change}%`
                                        : undefined
                                }
                                sparklineData={kpiSparklines?.vehicles}
                                icon={Truck}
                            />
                            </StaggerItem>
                            <StaggerItem duration={0.25}>
                            <FleetKpiCard
                                title="Active Drivers"
                                value={counts.drivers}
                                trend={
                                    (kpiTrends?.trips?.direction as
                                        | 'up'
                                        | 'down'
                                        | 'flat') ?? undefined
                                }
                                trendValue={
                                    kpiTrends?.trips
                                        ? `${kpiTrends.trips.change >= 0 ? '+' : ''}${kpiTrends.trips.change}%`
                                        : undefined
                                }
                                sparklineData={kpiSparklines?.trips}
                                icon={Users}
                            />
                            </StaggerItem>
                            <StaggerItem duration={0.25}>
                            <FleetKpiCard
                                title="Open Work Orders"
                                value={counts.work_orders}
                                trend={
                                    (kpiTrends?.work_orders?.direction as
                                        | 'up'
                                        | 'down'
                                        | 'flat') ?? undefined
                                }
                                trendValue={
                                    kpiTrends?.work_orders
                                        ? `${kpiTrends.work_orders.change >= 0 ? '+' : ''}${kpiTrends.work_orders.change}%`
                                        : undefined
                                }
                                sparklineData={kpiSparklines?.work_orders}
                                icon={Wrench}
                                subtitle={
                                    overdueWorkOrders > 0
                                        ? `${overdueWorkOrders} overdue`
                                        : undefined
                                }
                            />
                            </StaggerItem>
                            <StaggerItem duration={0.25}>
                            <FleetKpiCard
                                title="Active Alerts"
                                value={
                                    counts.alerts_open ??
                                    counts.alerts ??
                                    activeAlerts
                                }
                                trend={
                                    (kpiTrends?.alerts?.direction as
                                        | 'up'
                                        | 'down'
                                        | 'flat') ?? undefined
                                }
                                trendValue={
                                    kpiTrends?.alerts
                                        ? `${kpiTrends.alerts.change >= 0 ? '+' : ''}${kpiTrends.alerts.change}%`
                                        : undefined
                                }
                                sparklineData={kpiSparklines?.alerts}
                                icon={Bell}
                            />
                            </StaggerItem>
                        </StaggerList>
                        </StaggerItem>

                        {/* ============================================= */}
                        {/* Section 3 — Primary Charts                     */}
                        {/* ============================================= */}
                        <StaggerItem duration={0.25}>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-7">
                            {/* Fleet Activity — col-span-4 */}
                            <FleetChartCard
                                title="Fleet Activity"
                                description="Trips and work orders over the last 30 days"
                                className="lg:col-span-4"
                            >
                                {fleetActivity.length === 0 ? (
                                    <div className="flex h-[280px] items-center justify-center text-sm text-muted-foreground">
                                        No activity data available.
                                    </div>
                                ) : (
                                    <ChartContainer
                                        config={fleetActivityConfig}
                                        className="h-[280px] w-full"
                                    >
                                        <AreaChart
                                            data={fleetActivity}
                                            margin={{
                                                top: 8,
                                                right: 8,
                                                left: 0,
                                                bottom: 0,
                                            }}
                                        >
                                            <defs>
                                                <linearGradient
                                                    id="fillTrips"
                                                    x1="0"
                                                    y1="0"
                                                    x2="0"
                                                    y2="1"
                                                >
                                                    <stop
                                                        offset="0%"
                                                        stopColor="var(--color-trips)"
                                                        stopOpacity={0.3}
                                                    />
                                                    <stop
                                                        offset="100%"
                                                        stopColor="var(--color-trips)"
                                                        stopOpacity={0.05}
                                                    />
                                                </linearGradient>
                                                <linearGradient
                                                    id="fillWorkOrders"
                                                    x1="0"
                                                    y1="0"
                                                    x2="0"
                                                    y2="1"
                                                >
                                                    <stop
                                                        offset="0%"
                                                        stopColor="var(--color-work_orders)"
                                                        stopOpacity={0.3}
                                                    />
                                                    <stop
                                                        offset="100%"
                                                        stopColor="var(--color-work_orders)"
                                                        stopOpacity={0.05}
                                                    />
                                                </linearGradient>
                                            </defs>
                                            <CartesianGrid
                                                strokeDasharray="3 3"
                                                vertical={false}
                                            />
                                            <XAxis
                                                dataKey="label"
                                                tickLine={false}
                                                axisLine={false}
                                                fontSize={11}
                                                interval="preserveStartEnd"
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                                fontSize={11}
                                                allowDecimals={false}
                                            />
                                            <ChartTooltip
                                                content={
                                                    <ChartTooltipContent />
                                                }
                                            />
                                            <ChartLegend
                                                content={
                                                    <ChartLegendContent />
                                                }
                                            />
                                            <Area
                                                type="monotone"
                                                dataKey="trips"
                                                stroke="var(--color-trips)"
                                                fill="url(#fillTrips)"
                                                strokeWidth={2}
                                            />
                                            <Area
                                                type="monotone"
                                                dataKey="work_orders"
                                                stroke="var(--color-work_orders)"
                                                fill="url(#fillWorkOrders)"
                                                strokeWidth={2}
                                            />
                                        </AreaChart>
                                    </ChartContainer>
                                )}
                            </FleetChartCard>

                            {/* Cost Breakdown — col-span-3 */}
                            <FleetChartCard
                                title="Cost Breakdown"
                                description="Fuel, maintenance, and insurance (30 days)"
                                className="lg:col-span-3"
                            >
                                {costBreakdown.every((c) => c.value === 0) ? (
                                    <div className="flex h-[280px] items-center justify-center text-sm text-muted-foreground">
                                        No cost data available.
                                    </div>
                                ) : (
                                    <ChartContainer
                                        config={costBreakdownConfig}
                                        className="mx-auto h-[280px] w-full max-w-[300px]"
                                    >
                                        <PieChart>
                                            <ChartTooltip
                                                content={
                                                    <ChartTooltipContent
                                                        formatter={(
                                                            value,
                                                            name,
                                                        ) =>
                                                            `${name}: £${Number(value).toLocaleString()}`
                                                        }
                                                    />
                                                }
                                            />
                                            <Pie
                                                data={costBreakdown}
                                                dataKey="value"
                                                nameKey="name"
                                                innerRadius={60}
                                                outerRadius={90}
                                                paddingAngle={3}
                                            >
                                                {costBreakdown.map(
                                                    (entry, index) => (
                                                        <Cell
                                                            key={entry.name}
                                                            fill={
                                                                CHART_COLORS[
                                                                    index %
                                                                        CHART_COLORS.length
                                                                ]
                                                            }
                                                        />
                                                    ),
                                                )}
                                            </Pie>
                                            <ChartLegend
                                                content={
                                                    <ChartLegendContent />
                                                }
                                            />
                                        </PieChart>
                                    </ChartContainer>
                                )}
                            </FleetChartCard>
                        </div>
                        </StaggerItem>

                        {/* ============================================= */}
                        {/* Section 4 — AI Intelligence Layer               */}
                        {/* ============================================= */}
                        <StaggerItem duration={0.25}>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-7">
                            {/* AI Panel — col-span-4 */}
                            <FleetAiPanel
                                predictions={aiPredictions}
                                className="lg:col-span-4"
                            />

                            {/* Fuel Spend Trend — col-span-3 */}
                            <FleetChartCard
                                title="Fuel Spend Trend"
                                description="Daily fuel cost over 30 days"
                                className="lg:col-span-3"
                            >
                                {fuelCostTrend.length === 0 ? (
                                    <div className="flex h-[280px] items-center justify-center text-sm text-muted-foreground">
                                        No fuel data available.
                                    </div>
                                ) : (
                                    <ChartContainer
                                        config={fuelTrendConfig}
                                        className="h-[280px] w-full"
                                    >
                                        <LineChart
                                            data={fuelCostTrend}
                                            margin={{
                                                top: 8,
                                                right: 8,
                                                left: 0,
                                                bottom: 0,
                                            }}
                                        >
                                            <CartesianGrid
                                                strokeDasharray="3 3"
                                                vertical={false}
                                            />
                                            <XAxis
                                                dataKey="label"
                                                tickLine={false}
                                                axisLine={false}
                                                fontSize={11}
                                                interval="preserveStartEnd"
                                            />
                                            <YAxis
                                                tickLine={false}
                                                axisLine={false}
                                                fontSize={11}
                                                tickFormatter={(v) => `£${v}`}
                                            />
                                            <ChartTooltip
                                                content={
                                                    <ChartTooltipContent
                                                        formatter={(value) =>
                                                            `£${Number(value).toLocaleString()}`
                                                        }
                                                    />
                                                }
                                            />
                                            <Line
                                                type="monotone"
                                                dataKey="cost"
                                                stroke="var(--color-cost)"
                                                strokeWidth={2}
                                                dot={false}
                                            />
                                        </LineChart>
                                    </ChartContainer>
                                )}
                            </FleetChartCard>
                        </div>
                        </StaggerItem>

                        {/* ============================================= */}
                        {/* Section 5 — Operational Detail                  */}
                        {/* ============================================= */}
                        <StaggerItem duration={0.25}>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            {/* Recent Alerts */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                        <AlertTriangle className="size-4 text-muted-foreground" />
                                        Recent Alerts
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-0">
                                    {activeAlerts === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            No active alerts.
                                        </p>
                                    ) : (
                                        <div className="space-y-3">
                                            <p className="text-sm text-muted-foreground">
                                                {activeAlerts} active alert
                                                {activeAlerts !== 1
                                                    ? 's'
                                                    : ''}{' '}
                                                in your fleet.
                                            </p>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href="/fleet/alerts?status=active">
                                                    View all alerts
                                                </Link>
                                            </Button>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Upcoming Maintenance */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                        <Calendar className="size-4 text-muted-foreground" />
                                        Upcoming Maintenance
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-0">
                                    {upcomingMaintenance.length === 0 ? (
                                        <p className="text-sm text-muted-foreground">
                                            No maintenance scheduled within 14
                                            days.
                                        </p>
                                    ) : (
                                        <ul className="divide-y divide-border">
                                            {upcomingMaintenance.map((item) => (
                                                <li
                                                    key={item.id}
                                                    className="flex items-center justify-between gap-2 py-2 first:pt-0 last:pb-0"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium">
                                                            {item.vehicle_name}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {item.service_type
                                                                .replace(
                                                                    /_/g,
                                                                    ' ',
                                                                )
                                                                .replace(
                                                                    /^\w/,
                                                                    (c) =>
                                                                        c.toUpperCase(),
                                                                )}
                                                        </p>
                                                    </div>
                                                    <span className="shrink-0 text-xs text-muted-foreground">
                                                        {new Date(
                                                            item.next_service_due_date,
                                                        ).toLocaleDateString(
                                                            'en-GB',
                                                            {
                                                                day: 'numeric',
                                                                month: 'short',
                                                            },
                                                        )}
                                                    </span>
                                                </li>
                                            ))}
                                        </ul>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Driver Safety Distribution */}
                            <FleetChartCard
                                title="Driver Safety"
                                description="Distribution by safety score"
                            >
                                {driverSafety.every((d) => d.value === 0) ? (
                                    <div className="flex h-[200px] items-center justify-center text-sm text-muted-foreground">
                                        No driver data.
                                    </div>
                                ) : (
                                    <ChartContainer
                                        config={safetyConfig}
                                        className="mx-auto h-[200px] w-full max-w-[240px]"
                                    >
                                        <PieChart>
                                            <ChartTooltip
                                                content={
                                                    <ChartTooltipContent />
                                                }
                                            />
                                            <Pie
                                                data={driverSafety}
                                                dataKey="value"
                                                nameKey="name"
                                                innerRadius={45}
                                                outerRadius={70}
                                                paddingAngle={3}
                                            >
                                                {driverSafety.map(
                                                    (entry, index) => (
                                                        <Cell
                                                            key={entry.name}
                                                            fill={
                                                                index === 0
                                                                    ? 'var(--chart-1)'
                                                                    : index ===
                                                                        1
                                                                      ? 'var(--chart-4)'
                                                                      : 'var(--chart-5)'
                                                            }
                                                        />
                                                    ),
                                                )}
                                            </Pie>
                                            <ChartLegend
                                                content={
                                                    <ChartLegendContent />
                                                }
                                            />
                                        </PieChart>
                                    </ChartContainer>
                                )}
                            </FleetChartCard>
                        </div>
                        </StaggerItem>
                    </StaggerList>
                )}

                {/* ============================================= */}
                {/* Section 6 — Fleet Map (collapsible, closed)    */}
                {/* ============================================= */}
                <Card className="overflow-hidden">
                    <button
                        type="button"
                        onClick={() => setMapOpen((prev) => !prev)}
                        className="flex w-full items-center justify-between px-6 py-4 text-left"
                    >
                        <div className="flex items-center gap-2">
                            <MapPinned className="size-4 text-primary" />
                            <span className="text-sm font-semibold">
                                Fleet Map
                            </span>
                            {liveMapVehicles.length > 0 && (
                                <span className="rounded bg-emerald-500/20 px-1.5 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                    {liveMapVehicles.length} vehicle
                                    {liveMapVehicles.length !== 1 ? 's' : ''}
                                </span>
                            )}
                            {positionsUpdatedAt && (
                                <span className="text-xs text-muted-foreground">
                                    Updated{' '}
                                    {new Date(
                                        positionsUpdatedAt,
                                    ).toLocaleTimeString()}
                                </span>
                            )}
                        </div>
                        {mapOpen ? (
                            <ChevronUp className="size-4 text-muted-foreground" />
                        ) : (
                            <ChevronDown className="size-4 text-muted-foreground" />
                        )}
                    </button>

                    {mapOpen && (
                        <div className="border-t">
                            <div
                                className="relative"
                                style={{ height: '400px' }}
                            >
                                <FleetMap
                                    center={mapCenter}
                                    zoom={
                                        liveMapVehicles.length >= 2 ? 8 : 10
                                    }
                                    mapContainerStyle={{
                                        width: '100%',
                                        height: '100%',
                                    }}
                                    onMapLoad={setMapInstance}
                                    onMapUnmount={() => setMapInstance(null)}
                                >
                                    {mapGeofences?.map((gf) => (
                                        <FleetMapPolygon
                                            key={gf.id}
                                            paths={[gf.paths]}
                                        />
                                    ))}
                                    {mapPolylines?.map((pl) => (
                                        <FleetMapPolyline
                                            key={pl.trip_id}
                                            path={pl.path}
                                        />
                                    ))}
                                    <FleetMapClusterer
                                        map={mapInstance}
                                        vehicles={liveMapVehicles}
                                        onSelectVehicle={(id) =>
                                            setSelectedVehicleId((prev) =>
                                                prev === id ? null : id,
                                            )
                                        }
                                    />
                                    {!isMobile &&
                                        liveMapVehicles.map((v) =>
                                            selectedVehicleId === v.id ? (
                                                <FleetMapInfoWindow
                                                    key={`iw-${v.id}`}
                                                    position={{
                                                        lat: v.lat,
                                                        lng: v.lng,
                                                    }}
                                                    onCloseClick={() =>
                                                        setSelectedVehicleId(
                                                            null,
                                                        )
                                                    }
                                                >
                                                    <div className="min-w-[160px] space-y-2">
                                                        <p className="font-semibold">
                                                            {v.registration}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {v.source ===
                                                            'home'
                                                                ? 'Home location'
                                                                : 'Current position'}
                                                        </p>
                                                        <div className="flex flex-col gap-1">
                                                            <Link
                                                                href={`/fleet/vehicles/${v.id}`}
                                                                className="text-xs font-medium text-primary hover:underline"
                                                            >
                                                                View vehicle →
                                                            </Link>
                                                            <Link
                                                                href={`/fleet/work-orders/create?vehicle_id=${v.id}`}
                                                                className="text-xs font-medium text-primary hover:underline"
                                                            >
                                                                Create work
                                                                order →
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </FleetMapInfoWindow>
                                            ) : null,
                                        )}
                                </FleetMap>
                                {liveMapVehicles.length === 0 && (
                                    <div className="pointer-events-none absolute inset-0 flex items-center justify-center bg-muted/40">
                                        <p className="rounded-lg border border-border bg-card/95 px-4 py-3 text-center text-sm text-muted-foreground shadow-sm">
                                            No vehicle locations yet. Set
                                            current position or home location on
                                            vehicles to see them here.
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </Card>

                {/* Mobile map vehicle sheet */}
                {isMobile && (
                    <Sheet
                        open={!!selectedVehicle}
                        onOpenChange={(open) =>
                            !open && setSelectedVehicleId(null)
                        }
                    >
                        <SheetContent
                            side="bottom"
                            className="pb-[max(1rem,env(safe-area-inset-bottom))]"
                        >
                            <SheetHeader>
                                <SheetTitle>
                                    {selectedVehicle?.registration ?? 'Vehicle'}
                                </SheetTitle>
                            </SheetHeader>
                            {selectedVehicle && (
                                <div className="space-y-4 px-4">
                                    <p className="text-sm text-muted-foreground">
                                        {selectedVehicle.source === 'home'
                                            ? 'Home location'
                                            : 'Current position'}
                                    </p>
                                    <div className="flex flex-col gap-2">
                                        <Button
                                            asChild
                                            className="min-h-11 w-full"
                                        >
                                            <Link
                                                href={`/fleet/vehicles/${selectedVehicle.id}`}
                                            >
                                                View vehicle
                                            </Link>
                                        </Button>
                                        <Button
                                            asChild
                                            variant="outline"
                                            className="min-h-11 w-full"
                                        >
                                            <Link
                                                href={`/fleet/work-orders/create?vehicle_id=${selectedVehicle.id}`}
                                            >
                                                Create work order
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </SheetContent>
                    </Sheet>
                )}
            </FleetPageShell>
        </AppLayout>
    );
}
