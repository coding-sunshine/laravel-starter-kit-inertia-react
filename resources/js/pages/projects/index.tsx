import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Building2, Plus } from 'lucide-react';

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
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<ProjectsTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Properties', href: '/projects' },
    { title: 'Projects', href: '/projects' },
];

export default function ProjectsIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add project',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/projects/create'),
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Projects" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="projects-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Projects</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<ProjectsTableRow>
                    tableData={tableData}
                    tableName="projects"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
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
            </div>
        </AppSidebarLayout>
    );
}
