import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Activity, Flame, TrendingUp, Users, Zap } from 'lucide-react';

interface LeadStats {
    total_leads: number;
    hot_leads: number;
    engagement_today: number;
    active_sequences: number;
}

interface RecentLead {
    id: number;
    first_name: string;
    last_name: string | null;
    stage: string;
    lead_score: number | null;
    contact_origin: string;
    created_at: string;
}

interface ActiveSequence {
    id: number;
    name: string;
    trigger_stage: string | null;
    enrollments_count: number;
}

interface Props {
    stats: LeadStats;
    recent_leads: RecentLead[];
    active_sequences: ActiveSequence[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Lead Generation', href: '/lead-generation' },
];

const STAGE_COLORS: Record<string, string> = {
    new: 'bg-blue-100 text-blue-700',
    warm: 'bg-orange-100 text-orange-700',
    hot: 'bg-red-100 text-red-700',
    qualified: 'bg-green-100 text-green-700',
    cold: 'bg-gray-100 text-gray-600',
    nurture: 'bg-purple-100 text-purple-700',
};

export default function LeadGenerationIndexPage({ stats, recent_leads, active_sequences }: Props) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Lead Generation" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">AI Lead Generation</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Multi-channel capture, nurture sequences, and AI-powered outreach
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Link
                            href="/nurture-sequences"
                            className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <Zap className="h-4 w-4" />
                            Nurture Sequences
                        </Link>
                        <Link
                            href="/cold-outreach"
                            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                        >
                            <TrendingUp className="h-4 w-4" />
                            Cold Outreach Builder
                        </Link>
                    </div>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    {[
                        {
                            label: 'Total Leads',
                            value: stats.total_leads,
                            icon: Users,
                            color: 'text-blue-600',
                            bg: 'bg-blue-50',
                        },
                        {
                            label: 'Hot Leads',
                            value: stats.hot_leads,
                            icon: Flame,
                            color: 'text-red-600',
                            bg: 'bg-red-50',
                        },
                        {
                            label: "Today's Engagements",
                            value: stats.engagement_today,
                            icon: Activity,
                            color: 'text-green-600',
                            bg: 'bg-green-50',
                        },
                        {
                            label: 'Active Sequences',
                            value: stats.active_sequences,
                            icon: Zap,
                            color: 'text-purple-600',
                            bg: 'bg-purple-50',
                        },
                    ].map(({ label, value, icon: Icon, color, bg }) => (
                        <div key={label} className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-gray-500">{label}</p>
                                <div className={`rounded-lg p-2 ${bg}`}>
                                    <Icon className={`h-4 w-4 ${color}`} />
                                </div>
                            </div>
                            <p className="mt-3 text-2xl font-bold text-gray-900">{value.toLocaleString()}</p>
                        </div>
                    ))}
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Recent Leads */}
                    <div className="rounded-xl border border-gray-100 bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <h2 className="font-semibold text-gray-900">Recent Leads</h2>
                            <Link href="/contacts" className="text-xs text-primary hover:underline">
                                View all →
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-50">
                            {recent_leads.length === 0 && (
                                <p className="px-5 py-8 text-center text-sm text-gray-400">No leads yet</p>
                            )}
                            {recent_leads.map((lead) => (
                                <div key={lead.id} className="flex items-center gap-3 px-5 py-3">
                                    <div className="flex-1 min-w-0">
                                        <p className="truncate text-sm font-medium text-gray-900">
                                            {lead.first_name} {lead.last_name}
                                        </p>
                                        <p className="text-xs text-gray-400">{lead.contact_origin}</p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${STAGE_COLORS[lead.stage] ?? 'bg-gray-100 text-gray-600'}`}
                                        >
                                            {lead.stage}
                                        </span>
                                        {lead.lead_score !== null && (
                                            <span className="text-xs text-gray-500">Score: {lead.lead_score}</span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Active Nurture Sequences */}
                    <div className="rounded-xl border border-gray-100 bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <h2 className="font-semibold text-gray-900">Active Nurture Sequences</h2>
                            <Link href="/nurture-sequences" className="text-xs text-primary hover:underline">
                                Manage →
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-50">
                            {active_sequences.length === 0 && (
                                <div className="px-5 py-8 text-center">
                                    <Zap className="mx-auto mb-2 h-6 w-6 text-gray-300" />
                                    <p className="text-sm text-gray-400">No active sequences</p>
                                    <Link
                                        href="/nurture-sequences"
                                        className="mt-2 inline-block text-xs text-primary hover:underline"
                                    >
                                        Create your first sequence
                                    </Link>
                                </div>
                            )}
                            {active_sequences.map((seq) => (
                                <div key={seq.id} className="flex items-center gap-3 px-5 py-3">
                                    <div className="flex-1 min-w-0">
                                        <p className="truncate text-sm font-medium text-gray-900">{seq.name}</p>
                                        {seq.trigger_stage && (
                                            <p className="text-xs text-gray-400">
                                                Auto-enrolls: <span className="font-medium">{seq.trigger_stage}</span> stage
                                            </p>
                                        )}
                                    </div>
                                    <span className="text-xs text-gray-500">{seq.enrollments_count} enrolled</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* AI Tools Section */}
                <div className="rounded-xl border border-gray-100 bg-gradient-to-br from-orange-50 to-amber-50 p-6">
                    <h2 className="mb-4 font-semibold text-gray-900">AI-Powered Tools</h2>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        {[
                            {
                                title: 'Landing Page Copy',
                                description: 'Generate high-converting copy from property listings',
                                href: '/cold-outreach',
                                icon: '✍️',
                            },
                            {
                                title: 'Cold Outreach Builder',
                                description: 'AI-suggested email/SMS templates with A/B variants',
                                href: '/cold-outreach',
                                icon: '📧',
                            },
                            {
                                title: 'Lead Brief Generator',
                                description: 'Auto-generate detailed contact profiles',
                                href: '/contacts',
                                icon: '🎯',
                            },
                        ].map((tool) => (
                            <Link
                                key={tool.title}
                                href={tool.href}
                                className="group rounded-lg border border-orange-200/60 bg-white p-4 transition-shadow hover:shadow-md"
                            >
                                <div className="mb-2 text-2xl">{tool.icon}</div>
                                <h3 className="text-sm font-semibold text-gray-900 group-hover:text-primary">
                                    {tool.title}
                                </h3>
                                <p className="mt-1 text-xs text-gray-500">{tool.description}</p>
                            </Link>
                        ))}
                    </div>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
