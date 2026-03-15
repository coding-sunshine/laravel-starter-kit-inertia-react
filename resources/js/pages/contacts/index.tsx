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
import { Plus, Users } from 'lucide-react';

export interface ContactsTableRow {
    id: number;
    first_name: string;
    last_name: string | null;
    type: string;
    stage: string | null;
    contact_origin: string;
    company_name: string | null;
    lead_score: number | null;
    last_contacted_at: string | null;
    next_followup_at: string | null;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<ContactsTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Contacts', href: '/contacts' },
];

const CONTACT_STAGES = [
    { label: 'New Lead', value: 'new_lead' },
    { label: 'Contacted', value: 'contacted' },
    { label: 'Qualified', value: 'qualified' },
    { label: 'Nurturing', value: 'nurturing' },
    { label: 'Hot', value: 'hot' },
    { label: 'Client', value: 'client' },
    { label: 'Inactive', value: 'inactive' },
];

export default function ContactsIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [
        {
            label: 'Add contact',
            icon: Plus,
            variant: 'default',
            onClick: () => router.visit('/admin/contacts/create'),
        },
    ];

    const rowActions: DataTableAction<ContactsTableRow>[] = [
        {
            id: 'view',
            label: 'View / Edit',
            onClick: (row) => router.visit(`/contacts/${row.id}`),
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
                    options: CONTACT_STAGES,
                    defaultValue: '',
                },
            ],
            onClick: (row) => {
                const values = (row as ContactsTableRow & { _formValues?: Record<string, unknown> })._formValues;
                if (!values?.stage) return;
                router.patch(
                    `/contacts/${row.id}/quick-edit`,
                    { stage: values.stage },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
        {
            id: 'schedule-followup',
            label: 'Schedule follow-up',
            form: [
                {
                    name: 'next_followup_at',
                    label: 'Follow-up date',
                    type: 'text',
                    required: true,
                    placeholder: 'YYYY-MM-DD',
                },
            ],
            onClick: (row) => {
                const values = (row as ContactsTableRow & { _formValues?: Record<string, unknown> })._formValues;
                if (!values?.next_followup_at) return;
                router.patch(
                    `/contacts/${row.id}/quick-edit`,
                    { next_followup_at: values.next_followup_at },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
    ];

    const bulkActions: DataTableBulkAction<ContactsTableRow>[] = [
        {
            id: 'bulk-stage-qualified',
            label: 'Mark as Qualified',
            onClick: (rows) => {
                router.post(
                    '/contacts/bulk-update',
                    { ids: rows.map((r) => r.id), data: { stage: 'qualified' } },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
        {
            id: 'bulk-stage-hot',
            label: 'Mark as Hot',
            onClick: (rows) => {
                router.post(
                    '/contacts/bulk-update',
                    { ids: rows.map((r) => r.id), data: { stage: 'hot' } },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
        {
            id: 'bulk-stage-inactive',
            label: 'Mark as Inactive',
            variant: 'destructive',
            onClick: (rows) => {
                router.post(
                    '/contacts/bulk-update',
                    { ids: rows.map((r) => r.id), data: { stage: 'inactive' } },
                    { preserveScroll: true, preserveState: true },
                );
            },
        },
    ];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Contacts" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="contacts-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Contacts</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<ContactsTableRow>
                    tableData={tableData}
                    tableName="contacts"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    onRowClick={(row) => router.visit(`/contacts/${row.id}`)}
                    aiBaseUrl="/data-table/ai/contacts"
                    actions={rowActions}
                    bulkActions={bulkActions}
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <Users className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No contacts found</p>
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
                        batchEdit: true,
                    }}
                    translations={{
                        noData: 'No contacts',
                        search: 'Search contacts',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) =>
                            `Select all ${count} contacts`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
