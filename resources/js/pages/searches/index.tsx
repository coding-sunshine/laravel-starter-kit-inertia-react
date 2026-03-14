import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Search, Plus } from 'lucide-react';

export interface SearchesTableRow {
    id: number;
    client_contact_id: number | null;
    agent_contact_id: number | null;
    budget_min: number | null;
    budget_max: number | null;
    bedrooms_min: number | null;
    bedrooms_max: number | null;
    notes: string | null;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<SearchesTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/searches' },
    { title: 'Property Searches', href: '/searches' },
];

export default function SearchesIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add search',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/property-searches/create'),
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Property Searches" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="searches-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Property Searches</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<SearchesTableRow>
                    tableData={tableData}
                    tableName="searches"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <Search className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No searches found</p>
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
                        exports: false,
                        filters: true,
                        density: true,
                        copyCell: true,
                        emptyStateIllustration: true,
                        keyboardNavigation: true,
                        shortcutsOverlay: true,
                    }}
                    translations={{
                        noData: 'No searches',
                        search: 'Search property searches',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) =>
                            `Select all ${count} searches`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
