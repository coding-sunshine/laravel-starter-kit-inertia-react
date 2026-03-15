import { Badge } from '@/components/ui/badge';
import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Building2, Grid3X3, List, MapPin, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useState } from 'react';

export interface ProjectsTableRow {
    id: number;
    title: string;
    stage: string;
    suburb: string | null;
    state: string | null;
    developer_name: string | null;
    min_price: number | null;
    max_price: number | null;
    total_lots: number | null;
    is_hot_property: boolean;
    is_featured: boolean;
    is_archived: boolean;
    image: string | null;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<ProjectsTableRow>;
    searchableColumns: string[];
    pageTitle?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Properties', href: '/projects' },
    { title: 'Projects', href: '/projects' },
];

const STAGE_COLORS: Record<string, { bg: string; text: string }> = {
    selling: { bg: 'bg-emerald-500', text: 'text-white' },
    pre_launch: { bg: 'bg-blue-500', text: 'text-white' },
    completed: { bg: 'bg-gray-500', text: 'text-white' },
    archived: { bg: 'bg-gray-400', text: 'text-white' },
};

function formatPrice(value: number | null): string {
    if (value === null || value === 0) return '';
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency: 'AUD',
        maximumFractionDigits: 0,
    }).format(value);
}

function ProjectCard({ project }: { project: ProjectsTableRow }) {
    const [imgError, setImgError] = useState(false);
    const stageStyle = STAGE_COLORS[project.stage] ?? STAGE_COLORS.selling;
    const location = [project.suburb, project.state].filter(Boolean).join(', ');
    const price = project.min_price
        ? `From ${formatPrice(project.min_price)}`
        : '';

    return (
        <Link
            href={`/projects/${project.id}`}
            className="group overflow-hidden rounded-xl border bg-card transition-all hover:-translate-y-0.5 hover:shadow-lg"
        >
            {/* Image */}
            <div className="relative aspect-video bg-muted">
                {project.image && !imgError ? (
                    <img
                        src={project.image}
                        alt={project.title}
                        className="h-full w-full object-cover"
                        onError={() => setImgError(true)}
                    />
                ) : (
                    <div className="flex h-full items-center justify-center">
                        <Building2 className="size-10 text-muted-foreground/20" />
                    </div>
                )}

                {/* Stage badge */}
                <div
                    className={`absolute left-2.5 top-2.5 flex items-center gap-1.5 rounded px-2 py-0.5 text-[11px] font-medium ${stageStyle.bg} ${stageStyle.text}`}
                >
                    <span className="inline-block size-1.5 rounded-full bg-white/70" />
                    {project.stage === 'pre_launch' ? 'Pre-Launch' : project.stage.charAt(0).toUpperCase() + project.stage.slice(1)}
                </div>

                {/* Hot badge */}
                {project.is_hot_property && (
                    <div className="absolute right-2.5 top-2.5 rounded bg-red-500 px-2 py-0.5 text-[11px] font-medium text-white">
                        Hot
                    </div>
                )}
            </div>

            {/* Body */}
            <div className="p-3.5">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0">
                        <h3 className="truncate text-sm font-semibold leading-tight group-hover:text-primary">
                            {project.title}
                        </h3>
                        {location && (
                            <p className="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                                <MapPin className="size-3 shrink-0" />
                                {location}
                            </p>
                        )}
                        {project.developer_name && (
                            <p className="mt-0.5 text-xs text-muted-foreground/70">
                                {project.developer_name}
                            </p>
                        )}
                    </div>
                    {price && (
                        <span className="shrink-0 text-sm font-semibold text-primary">
                            {price}
                        </span>
                    )}
                </div>

                {/* Lot pills */}
                {project.total_lots !== null && project.total_lots > 0 && (
                    <div className="mt-2.5 flex flex-wrap gap-1.5">
                        <Badge variant="outline" color="success" className="text-[10px]">
                            {project.total_lots} lots
                        </Badge>
                        {project.is_featured && (
                            <Badge variant="outline" color="warning" className="text-[10px]">
                                Featured
                            </Badge>
                        )}
                    </div>
                )}
            </div>
        </Link>
    );
}

export default function ProjectsIndexPage({
    tableData,
    searchableColumns = [],
    pageTitle,
}: Props) {
    const [viewMode, setViewMode] = useState<'table' | 'cards'>('cards');
    const title = pageTitle ?? 'Projects';
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add project',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/projects/create'),
        },
    ];

    const rows = tableData?.data ?? [];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="projects-table"
            >
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                        {tableData && (
                            <p className="text-muted-foreground">
                                {tableData.meta.total} results
                            </p>
                        )}
                    </div>
                    <div className="flex items-center gap-1 rounded-lg border p-0.5">
                        <Button
                            variant={viewMode === 'table' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setViewMode('table')}
                            aria-label="Table view"
                        >
                            <List className="h-4 w-4" />
                        </Button>
                        <Button
                            variant={viewMode === 'cards' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => setViewMode('cards')}
                            aria-label="Card view"
                        >
                            <Grid3X3 className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {viewMode === 'cards' ? (
                    rows.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {rows.map((project) => (
                                <ProjectCard key={project.id} project={project} />
                            ))}
                        </div>
                    ) : (
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <Building2 className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No projects found</p>
                                <p className="text-sm text-muted-foreground">
                                    Try adjusting your search or filters.
                                </p>
                            </div>
                        </div>
                    )
                ) : (
                    <DataTable<ProjectsTableRow>
                        tableData={tableData}
                        tableName="projects"
                        searchableColumns={searchableColumns}
                        debounceMs={300}
                        partialReloadKey="tableData"
                        onRowClick={(row) => router.visit(`/projects/${row.id}`)}
                        aiBaseUrl="/data-table/ai/projects"
                        emptyState={
                            <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                                <div className="rounded-full bg-muted p-4">
                                    <Building2 className="size-8 text-muted-foreground" />
                                </div>
                                <div>
                                    <p className="font-medium">No projects found</p>
                                    <p className="text-sm text-muted-foreground">
                                        Try adjusting your search or filters.
                                    </p>
                                </div>
                            </div>
                        }
                        headerActions={headerActions}
                        options={{
                            columnVisibility: true,
                            columnOrdering: true,
                            columnResizing: true,
                            columnPinning: true,
                            quickViews: true,
                            customQuickViews: true,
                            exports: true,
                            filters: true,
                            density: true,
                            copyCell: true,
                            emptyStateIllustration: true,
                            keyboardNavigation: true,
                            shortcutsOverlay: true,
                            searchHighlight: true,
                            stickyHeader: true,
                        }}
                        translations={{
                            noData: 'No projects',
                            search: 'Search projects',
                            clearAllFilters: 'Clear all filters',
                            density: 'Row density',
                            selectAllMatching: (count) =>
                                `Select all ${count} projects`,
                        }}
                    />
                )}
            </div>
        </AppSidebarLayout>
    );
}
