import { DataTable } from "laravel-data-table";
import type { DataTableResponse } from "laravel-data-table";
import { Head } from "@inertiajs/react";

interface Props {
    tableData: DataTableResponse<App.DataTables.PropertySearchDataTable>;
}

export default function SearchTablePage({ tableData }: Props) {
    return (
        <>
            <Head title="Searches" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Searches</h1>
                    <p className="text-muted-foreground">
                        {tableData.meta.total} results
                    </p>
                </div>
                <div className="fusion-table-card overflow-x-auto rounded-lg">
                    <DataTable<App.DataTables.PropertySearchDataTable>
                        tableData={tableData}
                        tableName="search"
                    />
                </div>
            </div>
        </>
    );
}
