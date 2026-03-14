import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { DollarSign, Plus } from 'lucide-react';

export interface CommissionsTableRow {
    id: number;
    sale_id: number;
    commission_type: string;
    agent_user_id: number | null;
    rate_percentage: number | null;
    amount: number;
    override_amount: boolean;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<CommissionsTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/commissions' },
    { title: 'Commissions', href: '/commissions' },
];

export default function CommissionsIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add commission',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/commissions/create'),
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Commissions" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="commissions-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Commissions</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<CommissionsTableRow>
                    tableData={tableData}
                    tableName="commissions"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <DollarSign className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No commissions found</p>
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
                        noData: 'No commissions',
                        search: 'Search commissions',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) =>
                            `Select all ${count} commissions`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
