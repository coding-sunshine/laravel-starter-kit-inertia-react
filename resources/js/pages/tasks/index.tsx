import { DataTable } from '@/components/data-table/data-table';
import type {
    DataTableHeaderAction,
    DataTableResponse,
} from '@/components/data-table/types';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CheckSquare } from 'lucide-react';

export interface TasksTableRow {
    id: number;
    title: string;
    contact_name: string | null;
    type: string;
    priority: string;
    due_at: string | null;
    is_completed: boolean;
    completed_at: string | null;
    created_at: string | null;
}

interface Props {
    tableData?: DataTableResponse<TasksTableRow>;
    searchableColumns: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/tasks' },
    { title: 'Tasks', href: '/tasks' },
];

export default function TasksIndexPage({
    tableData,
    searchableColumns = [],
}: Props) {
    const headerActions: DataTableHeaderAction[] = [];

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Tasks" />
            <div
                className="flex h-full flex-1 flex-col gap-4 p-4"
                data-pan="tasks-table"
            >
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Tasks</h1>
                    {tableData && (
                        <p className="text-muted-foreground">
                            {tableData.meta.total} results
                        </p>
                    )}
                </div>
                <DataTable<TasksTableRow>
                    tableData={tableData}
                    tableName="tasks"
                    searchableColumns={searchableColumns}
                    debounceMs={300}
                    partialReloadKey="tableData"
                    emptyState={
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-muted p-4">
                                <CheckSquare className="size-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium">No tasks found</p>
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
                        noData: 'No tasks',
                        search: 'Search tasks',
                        clearAllFilters: 'Clear all filters',
                        density: 'Row density',
                        selectAllMatching: (count) =>
                            `Select all ${count} tasks`,
                    }}
                />
            </div>
        </AppSidebarLayout>
    );
}
