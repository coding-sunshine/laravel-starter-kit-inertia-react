import { DataTable } from '@/components/data-table/data-table';
import type { DataTableResponse } from '@/components/data-table/types';
import { Head } from "@inertiajs/react";

interface Props {
    tableData: DataTableResponse<App.DataTables.SaleDataTable>;
}

export default function SaleTablePage({ tableData }: Props) {
    return (
        <>
            <Head title="Sales" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Sales</h1>
                    <p className="text-muted-foreground">
                        {tableData.meta.total} results
                    </p>
                </div>
                <div className="fusion-table-card overflow-x-auto rounded-lg">
                    <DataTable<App.DataTables.SaleDataTable>
                        tableData={tableData}
                        tableName="sale"
                    />
                </div>
            </div>
        </>
    );
}
