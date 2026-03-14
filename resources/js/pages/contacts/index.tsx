import { DataTable } from '@/components/data-table/data-table';
import type {
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
