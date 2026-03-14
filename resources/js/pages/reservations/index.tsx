import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableAction,
    DataTableBulkAction,
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

const RESERVATION_STAGES = [
    { label: 'Enquiry', value: 'enquiry' },
    { label: 'Qualified', value: 'qualified' },
    { label: 'Reservation', value: 'reservation' },
    { label: 'Contract', value: 'contract' },
    { label: 'Unconditional', value: 'unconditional' },
    { label: 'Settled', value: 'settled' },
];

const DEPOSIT_STATUSES = [
    { label: 'Pending', value: 'pending' },
    { label: 'Paid', value: 'paid' },
    { label: 'Waived', value: 'waived' },
    { label: 'Refunded', value: 'refunded' },
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

    const rowActions: DataTableAction<PropertyReservationsTableRow>[] = [
        {
            id: 'view',
            label: 'View / Edit',
            onClick: (row) => router.visit(`/admin/property-reservations/${row.id}/edit`),
        },
        {
            id: 'quick-edit-stage',
            label: 'Update stage',
            form: [
                {
                    name: 'stage',
                    label: 'Stage',
                    type: 'select',
                    required: true,
                    options: RESERVATION_STAGES,
                    defaultValue: '',
                },
            ],
            onClick: (row) => {
                const values = (row as PropertyReservationsTableRow & { _formValues?: Record<string, unknown> })._formValues;
                if (!values?.stage) return;
                router.patch(
                    `/reservations/${row.id}/quick-edit`,
                    { stage: values.stage },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
        {
            id: 'quick-edit-deposit',
            label: 'Update deposit status',
            form: [
                {
                    name: 'deposit_status',
                    label: 'Deposit Status',
                    type: 'select',
                    required: true,
                    options: DEPOSIT_STATUSES,
                    defaultValue: '',
                },
            ],
            onClick: (row) => {
                const values = (row as PropertyReservationsTableRow & { _formValues?: Record<string, unknown> })._formValues;
                if (!values?.deposit_status) return;
                router.patch(
                    `/reservations/${row.id}/quick-edit`,
                    { deposit_status: values.deposit_status },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
    ];

    const bulkActions: DataTableBulkAction<PropertyReservationsTableRow>[] = [
        {
            id: 'bulk-stage-reservation',
            label: 'Move to Reservation',
            onClick: (rows) => {
                router.post(
                    '/reservations/bulk-update',
                    { ids: rows.map((r) => r.id), data: { stage: 'reservation' } },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
        {
            id: 'bulk-stage-contract',
            label: 'Move to Contract',
            onClick: (rows) => {
                router.post(
                    '/reservations/bulk-update',
                    { ids: rows.map((r) => r.id), data: { stage: 'contract' } },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
        {
            id: 'bulk-deposit-paid',
            label: 'Mark deposit paid',
            onClick: (rows) => {
                router.post(
                    '/reservations/bulk-update',
                    { ids: rows.map((r) => r.id), data: { stage: rows[0]?.stage ?? 'reservation' } },
                    { preserveScroll: true, preserveState: true },
                );
            },
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
                    actions={rowActions}
                    bulkActions={bulkActions}
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
