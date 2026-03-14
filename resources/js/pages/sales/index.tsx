import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Plus, TrendingUp } from 'lucide-react';

export interface SalesTableRow {
    id: number;
    status: string;
    comms_in_total: number | null;
    comms_out_total: number | null;
    lot_id: number | null;
    project_id: number | null;
    client_contact_id: number | null;
    settled_at: string | null;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<SalesTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/sales' },
    { title: 'Sales', href: '/sales' },
];

export default function SalesIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add sale',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/sales/create'),
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Sales" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="sales-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Sales</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<SalesTableRow>
                    tableData={tableData}
                    tableName="sales"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <TrendingUp className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No sales found</p>
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
                        noData: 'No sales',
                        search: 'Search sales',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) =>
                            `Select all ${count} sales`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
