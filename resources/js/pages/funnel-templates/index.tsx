import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle, Layers, PlusCircle, Users } from 'lucide-react';

interface FunnelTemplate {
    id: number;
    name: string;
    type: string;
    description: string | null;
    is_active: boolean;
    instances_count: number;
}

interface Stats {
    total_templates: number;
    active_instances: number;
    completed_instances: number;
}

interface Props {
    templates: {
        data: FunnelTemplate[];
        current_page: number;
        last_page: number;
    };
    stats: Stats;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Funnel Templates', href: '/funnel/templates' },
];

const TYPE_LABELS: Record<string, string> = {
    'co-living': 'Co-Living',
    rooming: 'Rooming House',
    'dual-occ': 'Dual Occupancy',
    generic: 'Generic',
};

const TYPE_COLORS: Record<string, string> = {
    'co-living': 'bg-blue-100 text-blue-700',
    rooming: 'bg-green-100 text-green-700',
    'dual-occ': 'bg-orange-100 text-orange-700',
    generic: 'bg-gray-100 text-gray-700',
};

export default function FunnelTemplatesIndexPage({ templates, stats }: Props) {
    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Funnel Templates" />
            <div
                className="flex h-full flex-1 flex-col gap-6 p-6"
                data-pan="funnel-templates-tab"
            >
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Funnel Engine</h1>
                        <p className="text-sm text-gray-500">
                            Manage automated lead nurture funnels and track contact progression
                        </p>
                    </div>
                    <Link
                        href="/funnel/templates/create"
                        className="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        <PlusCircle className="size-4" />
                        Create Template
                    </Link>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-3 gap-4">
                    <div className="rounded-xl border bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50">
                                <Layers className="size-5 text-indigo-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{stats.total_templates}</p>
                                <p className="text-xs text-gray-500">Total Templates</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-xl border bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50">
                                <Users className="size-5 text-green-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{stats.active_instances}</p>
                                <p className="text-xs text-gray-500">Active Enrollments</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-xl border bg-white p-5 shadow-sm">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50">
                                <CheckCircle className="size-5 text-blue-600" />
                            </div>
                            <div>
                                <p className="text-2xl font-bold text-gray-900">{stats.completed_instances}</p>
                                <p className="text-xs text-gray-500">Completed</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Templates List */}
                {templates.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center gap-4 rounded-xl border bg-white py-16 text-center">
                        <div className="rounded-full bg-gray-50 p-4">
                            <Layers className="size-8 text-gray-300" />
                        </div>
                        <div>
                            <p className="font-medium text-gray-700">No funnel templates yet</p>
                            <p className="mt-1 text-sm text-gray-400">
                                Create your first funnel template to start automating lead nurture.
                            </p>
                        </div>
                        <Link
                            href="/funnel/templates/create"
                            className="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                        >
                            <PlusCircle className="size-4" />
                            Create Template
                        </Link>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {templates.data.map((template) => (
                            <div
                                key={template.id}
                                className="rounded-xl border bg-white p-5 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <div className="flex items-start justify-between mb-3">
                                    <h3 className="font-semibold text-gray-900">{template.name}</h3>
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                            TYPE_COLORS[template.type] ?? 'bg-gray-100 text-gray-700'
                                        }`}
                                    >
                                        {TYPE_LABELS[template.type] ?? template.type}
                                    </span>
                                </div>
                                {template.description && (
                                    <p className="text-sm text-gray-500 mb-3 line-clamp-2">
                                        {template.description}
                                    </p>
                                )}
                                <div className="flex items-center justify-between text-xs text-gray-400">
                                    <span className="flex items-center gap-1">
                                        <Users className="size-3" />
                                        {template.instances_count} enrolled
                                    </span>
                                    <span
                                        className={`flex items-center gap-1 ${
                                            template.is_active ? 'text-green-600' : 'text-gray-400'
                                        }`}
                                    >
                                        <span
                                            className={`size-1.5 rounded-full ${
                                                template.is_active ? 'bg-green-500' : 'bg-gray-400'
                                            }`}
                                        />
                                        {template.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
