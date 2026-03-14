import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertCircle, Info, MessageSquare, Zap } from 'lucide-react';

interface Contact {
    id: number;
    first_name: string;
    last_name: string | null;
    stage: string;
    lead_score: number | null;
    contact_origin: string;
}

interface CoachingTip {
    type: 'action' | 'warning' | 'info' | 'script';
    text: string;
}

interface Brief {
    brief: string;
    generated_at: string;
    model: string;
}

interface Props {
    contact: Contact;
    brief: Brief;
    score: number;
    coaching_tips: CoachingTip[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Lead Generation', href: '/lead-generation' },
    { title: 'Coaching Panel', href: '#' },
];

const TIP_STYLES: Record<CoachingTip['type'], { bg: string; icon: React.ComponentType<{ className?: string }> }> = {
    action: { bg: 'bg-green-50 border-green-200 text-green-800', icon: Zap },
    warning: { bg: 'bg-amber-50 border-amber-200 text-amber-800', icon: AlertCircle },
    info: { bg: 'bg-blue-50 border-blue-200 text-blue-800', icon: Info },
    script: { bg: 'bg-purple-50 border-purple-200 text-purple-800', icon: MessageSquare },
};

export default function CoachingPage({ contact, brief, score, coaching_tips }: Props) {
    const scoreColor = score >= 70 ? 'text-green-600' : score >= 40 ? 'text-orange-600' : 'text-gray-500';
    const scoreBg = score >= 70 ? 'bg-green-100' : score >= 40 ? 'bg-orange-100' : 'bg-gray-100';

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title={`Coaching — ${contact.first_name} ${contact.last_name ?? ''}`} />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            Coaching: {contact.first_name} {contact.last_name}
                        </h1>
                        <p className="mt-1 text-sm text-gray-500">
                            AI-powered suggestions to help close this lead
                        </p>
                    </div>
                    <div className={`flex items-center gap-2 rounded-xl px-4 py-2 ${scoreBg}`}>
                        <span className="text-sm text-gray-600">Lead Score</span>
                        <span className={`text-2xl font-bold ${scoreColor}`}>{score}</span>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {/* Lead Brief */}
                    <div className="lg:col-span-2 space-y-4">
                        <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <div className="mb-4 flex items-center justify-between">
                                <h2 className="font-semibold text-gray-900">Lead Brief</h2>
                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">
                                    {brief.model}
                                </span>
                            </div>
                            <p className="text-sm leading-relaxed text-gray-700">{brief.brief}</p>
                            <p className="mt-3 text-xs text-gray-400">
                                Generated {new Date(brief.generated_at).toLocaleString()}
                            </p>
                        </div>

                        {/* Coaching Tips */}
                        <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                            <h2 className="mb-4 font-semibold text-gray-900">Coaching Tips</h2>
                            <div className="space-y-3">
                                {coaching_tips.map((tip, i) => {
                                    const { bg, icon: Icon } = TIP_STYLES[tip.type];

                                    return (
                                        <div key={i} className={`flex gap-3 rounded-lg border p-3 ${bg}`}>
                                            <Icon className="mt-0.5 h-4 w-4 flex-shrink-0" />
                                            <div>
                                                {tip.type === 'script' && (
                                                    <p className="mb-1 text-xs font-semibold uppercase tracking-wide opacity-70">
                                                        Script
                                                    </p>
                                                )}
                                                <p className="text-sm">{tip.text}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>

                    {/* Contact Sidebar */}
                    <div className="space-y-4">
                        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <h2 className="mb-3 font-semibold text-gray-900">Contact Details</h2>
                            <dl className="space-y-2">
                                <div>
                                    <dt className="text-xs text-gray-400">Stage</dt>
                                    <dd className="text-sm font-medium text-gray-900 capitalize">{contact.stage}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-gray-400">Channel</dt>
                                    <dd className="text-sm text-gray-700">{contact.contact_origin}</dd>
                                </div>
                            </dl>
                        </div>

                        <div className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
                            <h2 className="mb-3 font-semibold text-gray-900">Quick Actions</h2>
                            <div className="space-y-2">
                                <Link
                                    href={`/contacts`}
                                    className="block w-full rounded-lg bg-primary px-4 py-2 text-center text-sm font-medium text-white hover:bg-primary/90"
                                >
                                    View Full Contact
                                </Link>
                                <Link
                                    href="/lead-generation"
                                    className="block w-full rounded-lg border border-gray-200 px-4 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    ← Back to Lead Generation
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
