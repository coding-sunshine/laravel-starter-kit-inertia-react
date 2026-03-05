import { DataTable } from '@/components/data-table/data-table';
import type { DataTableResponse } from '@/components/data-table/types';
import { Head } from "@inertiajs/react";

// After running php artisan typescript:transform, use:
// App.DataTables.ProjectDataTable as the generic type

interface Props {
    tableData: DataTableResponse<App.DataTables.ProjectDataTable>;
}

export default function ProjectTablePage({ tableData }: Props) {
    return (
        <>
            <Head title="Projects" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Projects</h1>
                    <p className="text-muted-foreground">
                        {tableData.meta.total} results
                    </p>
                </div>
                <div className="fusion-table-card overflow-x-auto rounded-lg">
                    <DataTable<App.DataTables.ProjectDataTable>
                        tableData={tableData}
                        tableName="project"
                    />
                </div>
            </div>
        </>
    );
}