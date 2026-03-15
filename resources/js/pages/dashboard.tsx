import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    BarChart3,
    Bot,
    Bug,
    Building2,
    Calendar,
    CheckSquare,
    DollarSign,
    GitBranch,
    Mail,
    Sparkles,
    TrendingUp,
    UserPlus,
    Users,
} from 'lucide-react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    XAxis,
    YAxis,
} from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

interface CrmKpis {
    newContactsThisWeek: number;
    newContactsDelta: number;
    activeReservations: number;
    settledThisMonth: string;
    overdueTasksCount: number;
    staleContacts: number;
    totalContacts: number;
    totalLotsAvailable: number;
    totalLotsSold: number;
    priorityQueue: PriorityContact[];
}

interface FeaturedProject {
    id: number;
    title: string;
    location: string;
    priceRange: string | null;
    image: string | null;
    type: string | null;
    rentYield: string | null;
    totalLots: number | null;
    stage: string | null;
}

interface PriorityContact {
    id: number;
    name: string;
    stage: string;
    source: string;
    leadScore: number;
    daysSince: number;
}

interface PipelineStage {
    stage: string;
    count: number;
}

interface WeeklyStat {
    name: string;
    value: number;
}

interface DashboardProps {
    crmKpis?: CrmKpis;
    pipelineFunnel?: PipelineStage[];
    featuredProjects?: FeaturedProject[];
    aiInsight?: { summary: string; suggestions: string[] } | null;
    weeklyStats?: WeeklyStat[];
    usersCount?: number;
    orgsCount?: number;
    contactSubmissionsCount?: number;
    usersGrowthPercent?: number | null;
    orgsGrowthPercent?: number | null;
}

function getGreeting(): string {
    const h = new Date().getHours();
    if (h < 12) return 'Good morning';
    if (h < 18) return 'Good afternoon';
    return 'Good evening';
}

function daysSinceColor(days: number): string {
    if (days <= 3) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300';
    if (days <= 14) return 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300';
    if (days <= 30) return 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300';
    return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
}

function stageColor(stage: string): string {
    const s = stage.toLowerCase();
    if (s === 'new') return 'bg-blue-500';
    if (s === 'qualified' || s === 'property enquiry') return 'bg-emerald-500';
    if (s === 'proposal' || s === 'reservation') return 'bg-orange-500';
    if (s === 'converted' || s === 'settled') return 'bg-green-600';
    return 'bg-gray-400';
}

export default function Dashboard() {
    const { auth } = usePage<SharedData>().props;
    const props = usePage<SharedData & DashboardProps>().props;
    const isSuperAdmin = auth.roles?.includes('super-admin') ?? false;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-xl font-semibold tracking-tight">
                        {getGreeting()}, {auth.user.name}
                    </h2>
                </div>

                {/* CRM KPI Cards */}
                <CrmKpiCards kpis={props.crmKpis} />

                {/* Priority Work Queue + Pipeline Funnel */}
                <div className="grid gap-6 lg:grid-cols-5">
                    <div className="lg:col-span-3">
                        <PriorityWorkQueue contacts={props.crmKpis?.priorityQueue} />
                    </div>
                    <div className="lg:col-span-2">
                        <PipelineFunnel stages={props.pipelineFunnel} />
                    </div>
                </div>

                {/* Featured Properties */}
                <FeaturedProperties projects={props.featuredProjects} />

                {/* AI Insight */}
                <AiInsightCard insight={props.aiInsight} />

                {/* Activity Chart */}
                <ActivityChart data={props.weeklyStats ?? []} />

                {/* Bottom KPI row */}
                <BottomKpis kpis={props.crmKpis} />

                {/* Admin tools (super admin only) */}
                {isSuperAdmin && <AdminTools />}
            </div>

        </AppLayout>
    );
}

function CrmKpiCards({ kpis }: { kpis?: CrmKpis }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <KpiCard
                label="New Contacts"
                sublabel="This week"
                value={kpis?.newContactsThisWeek}
                icon={UserPlus}
                accent="border-l-4 border-l-blue-500"
                trend={kpis?.newContactsDelta}
                href="/contacts"
            />
            <KpiCard
                label="Overdue Tasks"
                sublabel="Action needed"
                value={kpis?.overdueTasksCount}
                icon={CheckSquare}
                accent="border-l-4 border-l-primary"
                href="/tasks"
            />
            <KpiCard
                label="Stale Contacts"
                sublabel="30+ days no contact"
                value={kpis?.staleContacts}
                icon={AlertTriangle}
                accent="border-l-4 border-l-red-500"
                href="/contacts"
            />
            <KpiCard
                label="Active Reservations"
                sublabel="In pipeline"
                value={kpis?.activeReservations}
                icon={Calendar}
                accent="border-l-4 border-l-emerald-500"
                href="/reservations"
            />
        </div>
    );
}

function KpiCard({
    label,
    sublabel,
    value,
    icon: Icon,
    accent,
    trend,
    href,
}: {
    label: string;
    sublabel: string;
    value?: number;
    icon: React.FC<{ className?: string }>;
    accent: string;
    trend?: number;
    href: string;
}) {
    return (
        <Link
            href={href}
            className={`flex flex-col gap-1 rounded-xl border bg-card p-5 transition-colors hover:bg-accent/50 ${accent}`}
        >
            <div className="flex items-center gap-2 text-muted-foreground">
                <Icon className="size-4" />
                <span className="text-sm font-medium">{label}</span>
            </div>
            <div className="flex items-end justify-between">
                {value !== undefined ? (
                    <span className="text-3xl font-bold">{value.toLocaleString()}</span>
                ) : (
                    <Skeleton className="h-9 w-16" />
                )}
                {trend !== undefined && trend !== 0 && (
                    <span
                        className={
                            trend > 0
                                ? 'text-xs font-medium text-emerald-600'
                                : 'text-xs font-medium text-red-600'
                        }
                    >
                        {trend > 0 ? '↑' : '↓'} {Math.abs(trend)}%
                    </span>
                )}
            </div>
            <span className="text-xs text-muted-foreground">{sublabel}</span>
        </Link>
    );
}

function PriorityWorkQueue({ contacts }: { contacts?: PriorityContact[] }) {
    return (
        <div className="rounded-xl border bg-card p-5">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="font-semibold">Priority Work Queue</h3>
                <Link
                    href="/contacts"
                    className="text-xs text-primary hover:underline"
                >
                    View all
                </Link>
            </div>
            {!contacts ? (
                <div className="space-y-3">
                    {[...Array(5)].map((_, i) => (
                        <Skeleton key={i} className="h-10 w-full" />
                    ))}
                </div>
            ) : contacts.length === 0 ? (
                <p className="py-8 text-center text-sm text-muted-foreground">
                    No contacts requiring attention
                </p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-xs text-muted-foreground">
                                <th className="pb-2 font-medium">Contact</th>
                                <th className="pb-2 font-medium">Stage</th>
                                <th className="pb-2 font-medium">Days Since</th>
                                <th className="pb-2 font-medium">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            {contacts.map((c) => (
                                <tr
                                    key={c.id}
                                    className="border-b last:border-0"
                                >
                                    <td className="py-2.5">
                                        <div className="flex items-center gap-2">
                                            <div className="flex size-7 items-center justify-center rounded-full bg-muted text-xs font-medium">
                                                {c.name
                                                    .split(' ')
                                                    .map((n) => n[0])
                                                    .join('')
                                                    .slice(0, 2)
                                                    .toUpperCase()}
                                            </div>
                                            <span className="font-medium">
                                                {c.name}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="py-2.5">
                                        <div className="flex items-center gap-1.5">
                                            <span
                                                className={`size-2 rounded-full ${stageColor(c.stage)}`}
                                            />
                                            <span className="text-xs">
                                                {c.stage}
                                            </span>
                                        </div>
                                    </td>
                                    <td className="py-2.5">
                                        <span
                                            className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${daysSinceColor(c.daysSince)}`}
                                        >
                                            {c.daysSince}d
                                        </span>
                                    </td>
                                    <td className="py-2.5">
                                        <div className="flex items-center gap-2">
                                            <div className="h-1.5 w-12 overflow-hidden rounded-full bg-muted">
                                                <div
                                                    className="h-full rounded-full bg-primary"
                                                    style={{
                                                        width: `${Math.min(c.leadScore, 100)}%`,
                                                    }}
                                                />
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                {c.leadScore}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

function PipelineFunnel({ stages }: { stages?: PipelineStage[] }) {
    const total = stages?.reduce((s, v) => s + v.count, 0) ?? 0;

    return (
        <div className="rounded-xl border bg-card p-5">
            <h3 className="mb-4 font-semibold">Pipeline Funnel</h3>
            {!stages ? (
                <div className="space-y-3">
                    {[...Array(6)].map((_, i) => (
                        <Skeleton key={i} className="h-6 w-full" />
                    ))}
                </div>
            ) : total === 0 ? (
                <p className="py-8 text-center text-sm text-muted-foreground">
                    No pipeline data yet
                </p>
            ) : (
                <div className="h-[220px]">
                    <ResponsiveContainer width="100%" height="100%">
                        <BarChart
                            data={stages}
                            layout="vertical"
                            margin={{ left: 0, right: 20 }}
                        >
                            <XAxis type="number" hide />
                            <YAxis
                                type="category"
                                dataKey="stage"
                                className="text-xs"
                                width={90}
                                tickLine={false}
                                axisLine={false}
                            />
                            <Bar
                                dataKey="count"
                                fill="var(--primary)"
                                radius={[0, 4, 4, 0]}
                                label={{
                                    position: 'right',
                                    className: 'text-xs fill-muted-foreground',
                                }}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}

function AiInsightCard({
    insight,
}: {
    insight?: { summary: string; suggestions: string[] } | null;
}) {
    if (insight === undefined) {
        return (
            <div className="rounded-xl border border-primary/20 bg-primary/5 p-5">
                <div className="flex items-center gap-2">
                    <Bot className="size-5 text-primary" />
                    <Skeleton className="h-4 w-48" />
                </div>
                <Skeleton className="mt-3 h-12 w-full" />
            </div>
        );
    }

    if (!insight) return null;

    return (
        <div className="rounded-xl border border-primary/20 bg-primary/5 p-5">
            <div className="flex items-center gap-2">
                <Bot className="size-5 text-primary" />
                <h3 className="font-semibold text-primary">AI Daily Insight</h3>
            </div>
            <p className="mt-2 text-sm">{insight.summary}</p>
            {insight.suggestions?.length > 0 && (
                <ul className="mt-3 space-y-1">
                    {insight.suggestions.map((s, i) => (
                        <li
                            key={i}
                            className="flex items-start gap-2 text-sm text-muted-foreground"
                        >
                            <Sparkles className="mt-0.5 size-3 shrink-0 text-primary" />
                            {s}
                        </li>
                    ))}
                </ul>
            )}
            <div className="mt-3">
                <Button size="sm" variant="outline" asChild>
                    <Link href="/ai/concierge">Ask AI</Link>
                </Button>
            </div>
        </div>
    );
}

function ActivityChart({ data }: { data: WeeklyStat[] }) {
    return (
        <div className="rounded-xl border bg-card p-5">
            <h3 className="mb-4 font-medium">Activity this week</h3>
            {data.length === 0 ? (
                <Skeleton className="h-[180px] w-full rounded" />
            ) : (
                <div className="h-[180px] w-full text-primary">
                    <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={data}>
                            <CartesianGrid
                                strokeDasharray="3 3"
                                className="stroke-muted"
                            />
                            <XAxis dataKey="name" className="text-xs" />
                            <YAxis className="text-xs" allowDecimals={false} />
                            <Area
                                type="monotone"
                                dataKey="value"
                                stroke="currentColor"
                                fill="currentColor"
                                fillOpacity={0.2}
                            />
                        </AreaChart>
                    </ResponsiveContainer>
                </div>
            )}
        </div>
    );
}

function FeaturedProperties({ projects }: { projects?: FeaturedProject[] }) {
    if (!projects || projects.length === 0) return null;

    return (
        <div className="rounded-xl border bg-card p-5">
            <div className="mb-4 flex items-center justify-between">
                <h3 className="font-semibold">Featured Properties ({projects.length})</h3>
                <Link href="/projects" className="text-xs text-primary hover:underline">
                    View all
                </Link>
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                {projects.map((p) => (
                    <Link
                        key={p.id}
                        href={`/projects`}
                        className="rounded-lg border p-3 transition-colors hover:bg-accent/50"
                    >
                        {p.image ? (
                            <img
                                src={p.image}
                                alt={p.title}
                                className="mb-2 h-24 w-full rounded object-cover"
                                onError={(e) => {
                                    e.currentTarget.style.display = 'none';
                                    e.currentTarget.nextElementSibling?.classList.remove('hidden');
                                }}
                            />
                        ) : null}
                        <div className={`mb-2 flex h-24 items-center justify-center rounded bg-muted text-muted-foreground ${p.image ? 'hidden' : ''}`}>
                            <Building2 className="size-8 opacity-30" />
                        </div>
                        <h4 className="text-sm font-semibold leading-tight">{p.title}</h4>
                        <p className="mt-0.5 text-xs text-muted-foreground">{p.location}</p>
                        {p.priceRange && (
                            <p className="mt-1 text-xs font-medium text-primary">{p.priceRange}</p>
                        )}
                        <div className="mt-2 flex flex-wrap gap-1">
                            {p.type && (
                                <span className="rounded-full bg-muted px-2 py-0.5 text-[10px]">{p.type}</span>
                            )}
                            {p.rentYield && (
                                <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] text-emerald-700">
                                    Yield: {p.rentYield}
                                </span>
                            )}
                            {p.totalLots != null && (
                                <span className="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] text-blue-700">
                                    {p.totalLots} lots
                                </span>
                            )}
                        </div>
                    </Link>
                ))}
            </div>
        </div>
    );
}

function BottomKpis({ kpis }: { kpis?: CrmKpis }) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <MiniKpi
                label="Total Contacts"
                value={kpis?.totalContacts?.toLocaleString()}
                icon={Users}
            />
            <MiniKpi
                label="Active Reservations"
                value={kpis?.activeReservations?.toString()}
                icon={Calendar}
            />
            <MiniKpi
                label="Settled This Month"
                value={kpis?.settledThisMonth}
                icon={DollarSign}
            />
            <MiniKpi
                label="Lots Available"
                value={kpis?.totalLotsAvailable?.toLocaleString()}
                icon={Building2}
            />
            <MiniKpi
                label="Lots Sold"
                value={kpis?.totalLotsSold?.toLocaleString()}
                icon={TrendingUp}
            />
        </div>
    );
}

function MiniKpi({
    label,
    value,
    icon: Icon,
}: {
    label: string;
    value?: string;
    icon: React.FC<{ className?: string }>;
}) {
    return (
        <div className="flex items-center gap-3 rounded-xl border bg-card px-4 py-3">
            <Icon className="size-4 text-muted-foreground" />
            <div>
                <p className="text-xs text-muted-foreground">{label}</p>
                {value !== undefined ? (
                    <p className="text-lg font-semibold">{value}</p>
                ) : (
                    <Skeleton className="mt-1 h-5 w-12" />
                )}
            </div>
        </div>
    );
}

function AdminTools() {
    return (
        <div className="rounded-lg border bg-card p-5">
            <h3 className="mb-4 font-medium">Admin tools</h3>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {[
                    { label: 'Horizon (queues)', href: '/horizon', icon: Activity },
                    { label: 'Waterline (workflows)', href: '/waterline', icon: GitBranch },
                    { label: 'Telescope (debug)', href: '/telescope', icon: Bug },
                    { label: 'Email templates', href: '/system/mail-templates', icon: Mail },
                    { label: 'Product analytics', href: '/system/analytics/product', icon: BarChart3 },
                ].map((tool) => (
                    <Button
                        key={tool.label}
                        variant="outline"
                        className="h-auto justify-start gap-2 py-3"
                        asChild
                    >
                        <a href={tool.href}>
                            <tool.icon className="size-4 text-muted-foreground" />
                            <span className="text-sm">{tool.label}</span>
                        </a>
                    </Button>
                ))}
            </div>
        </div>
    );
}
