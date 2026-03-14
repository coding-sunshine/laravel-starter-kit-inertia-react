import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CalendarClock, Plus } from 'lucide-react';

export interface PropertyReservationsTableRow {
    id: number;
    stage: string;
    deposit_status: string;
    purchase_price: number | null;
    lot_id: number | null;
    project_id: number | null;
    agent_contact_id: number | null;
    primary_contact_id: number | null;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<PropertyReservationsTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/reservations' },
    { title: 'Reservations', href: '/reservations' },
];

export default function ReservationsIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add reservation',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/property-reservations/create'),
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Reservations" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="reservations-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Reservations</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<PropertyReservationsTableRow>
                    tableData={tableData}
                    tableName="reservations"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <CalendarClock className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No reservations found</p>
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
                        noData: 'No reservations',
                        search: 'Search reservations',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) =>
                            `Select all ${count} reservations`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
