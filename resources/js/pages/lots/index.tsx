import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { LayoutGrid, Plus } from 'lucide-react';

export interface LotsTableRow {
    id: number;
    title: string | null;
    project_title: string | null;
    level: string | null;
    bedrooms: number | null;
    bathrooms: number | null;
    car: number | null;
    internal: number | null;
    total: number | null;
    price: number | null;
    title_status: string;
    weekly_rent: number | null;
    is_archived: boolean;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<LotsTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Properties', href: '/projects' },
    { title: 'Lots', href: '/lots' },
];

export default function LotsIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add lot',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/lots/create'),
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Lots" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="lots-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Lots</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<LotsTableRow>
                    tableData={tableData}
                    tableName="lots"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <LayoutGrid className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No lots found</p>
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
                        noData: 'No lots',
                        search: 'Search lots',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) => `Select all ${count} lots`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
