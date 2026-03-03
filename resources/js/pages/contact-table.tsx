import { DataTable } from "laravel-data-table";
import type { DataTableResponse } from "laravel-data-table";
import { Head } from "@inertiajs/react";

interface Props {
    tableData: DataTableResponse<App.DataTables.ContactDataTable>;
}

export default function ContactTablePage({ tableData }: Props) {
    return (
        <>
            <Head title="Contacts" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Contacts</h1>
                    <p className="text-muted-foreground">
                        {tableData.meta.total} results
                    </p>
                </div>
                <DataTable<App.DataTables.ContactDataTable>
                    tableData={tableData}
                    tableName="contact"
                />
            </div>
        </>
    );
}
