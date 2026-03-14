import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { MessageSquare, Plus } from 'lucide-react';

export interface EnquiriesTableRow {
    id: number;
    status: string;
    client_contact_id: number | null;
    agent_contact_id: number | null;
    lot_id: number | null;
    project_id: number | null;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<EnquiriesTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/enquiries' },
    { title: 'Enquiries', href: '/enquiries' },
];

export default function EnquiriesIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add enquiry',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/property-enquiries/create'),
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Enquiries" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="enquiries-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Enquiries</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<EnquiriesTableRow>
                    tableData={tableData}
                    tableName="enquiries"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <MessageSquare className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No enquiries found</p>
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
                        noData: 'No enquiries',
                        search: 'Search enquiries',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) =>
                            `Select all ${count} enquiries`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
