import { DataTable } from '@/components/data-table/data-table';
import type { DataTableResponse } from '@/components/data-table/types';
import { Head } from "@inertiajs/react";

interface Props {
    tableData: DataTableResponse<App.DataTables.PropertyReservationDataTable>;
}

export default function ReservationTablePage({ tableData }: Props) {
    return (
        <>
            <Head title="Reservations" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Reservations</h1>
                    <p className="text-muted-foreground">
                        {tableData.meta.total} results
                    </p>
                </div>
                <div className="fusion-table-card overflow-x-auto rounded-lg">
                    <DataTable<App.DataTables.PropertyReservationDataTable>
                        tableData={tableData}
                        tableName="reservation"
                    />
                </div>
            </div>
        </>
    );
}
