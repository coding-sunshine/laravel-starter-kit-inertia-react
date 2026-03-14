import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Plus, Users, Zap } from 'lucide-react';

interface SequenceStep {
    id: number;
    channel: string;
    subject: string | null;
    delay_days: number;
    step_order: number;
}

interface NurtureSequence {
    id: number;
    name: string;
    description: string | null;
    trigger_stage: string | null;
    is_active: boolean;
    enrollments_count: number;
    steps_count: number;
}

interface Props {
    sequences: {
        data: NurtureSequence[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Lead Generation', href: '/lead-generation' },
    { title: 'Nurture Sequences', href: '/nurture-sequences' },
];

const CHANNEL_ICONS: Record<string, string> = {
    email: '📧',
    sms: '📱',
    task: '✅',
};

export default function NurtureSequencesIndexPage({ sequences }: Props) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Nurture Sequences" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Nurture Sequences</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            AI-powered, multi-step drip campaigns for lead nurturing
                        </p>
                    </div>
                    <Link
                        href="/lead-generation"
                        className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                    >
                        <Plus className="h-4 w-4" />
                        New Sequence
                    </Link>
                </div>

                {/* Sequences List */}
                {sequences.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-200 bg-white p-12 text-center">
                        <Zap className="mx-auto mb-3 h-10 w-10 text-gray-300" />
                        <h3 className="font-semibold text-gray-700">No nurture sequences yet</h3>
                        <p className="mt-1 text-sm text-gray-400">
                            Create your first sequence to automate lead nurturing
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {sequences.data.map((seq) => (
                            <div
                                key={seq.id}
                                className="rounded-xl border border-gray-100 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
                            >
                                <div className="flex items-start justify-between">
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <h3 className="truncate font-semibold text-gray-900">{seq.name}</h3>
                                            <span
                                                className={`flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ${
                                                    seq.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-gray-100 text-gray-500'
                                                }`}
                                            >
                                                {seq.is_active ? 'Active' : 'Paused'}
                                            </span>
                                        </div>
                                        {seq.description && (
                                            <p className="mt-1 text-sm text-gray-500 line-clamp-2">
                                                {seq.description}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-4 flex items-center gap-4 text-sm text-gray-500">
                                    <span className="flex items-center gap-1">
                                        <Zap className="h-3.5 w-3.5" />
                                        {seq.steps_count} steps
                                    </span>
                                    <span className="flex items-center gap-1">
                                        <Users className="h-3.5 w-3.5" />
                                        {seq.enrollments_count} enrolled
                                    </span>
                                    {seq.trigger_stage && (
                                        <span className="rounded-full bg-orange-100 px-2 py-0.5 text-xs text-orange-700">
                                            Auto: {seq.trigger_stage}
                                        </span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {sequences.last_page > 1 && (
                    <div className="flex justify-center gap-2">
                        {Array.from({ length: sequences.last_page }, (_, i) => i + 1).map((page) => (
                            <Link
                                key={page}
                                href={`/nurture-sequences?page=${page}`}
                                className={`rounded-lg px-3 py-1.5 text-sm ${
                                    page === sequences.current_page
                                        ? 'bg-primary text-white'
                                        : 'border border-gray-200 text-gray-600 hover:bg-gray-50'
                                }`}
                            >
                                {page}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
