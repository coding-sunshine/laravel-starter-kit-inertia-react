import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { StatusPill } from '@/components/status-pill';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Truck } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface ArrivalRow {
    id: number;
    siding_id: number;
    vehicle_id: number;
    status: string;
    arrived_at: string;
    net_weight: string | null;
    unloaded_quantity: string | null;
    siding_code: string | null;
    siding_name: string | null;
    vehicle_number: string | null;
}

interface Props {
    tableData: DataTableResponse<ArrivalRow>;
    sidings: Siding[];
}

export default function RoadDispatchArrivalsIndex({
    tableData,
    sidings,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Road Dispatch', href: '/road-dispatch/arrivals' },
        { title: 'Vehicle Arrivals', href: '/road-dispatch/arrivals' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vehicle Arrivals" />
            <div className="space-y-6">
                <Heading
                    title="Vehicle Arrivals"
                    description="Record and view vehicle arrivals at siding"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/road-dispatch/arrivals/create">
                        <Button>
                            <Truck className="mr-2 size-4" />
                            Record arrival
                        </Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="Vehicle arrivals logged in a paper register at the gate, with manual tonnage tallying."
                    after="Digital arrival log with vehicle/driver details, linked to stock movement — no paper register needed."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Arrivals</CardTitle>
                        <CardDescription>
                            Filter by siding and date
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<ArrivalRow>
                            tableData={tableData}
                            tableName="road-dispatch-arrivals"
                            actions={[
                                {
                                    label: 'View',
                                    onClick: (row) =>
                                        router.visit(
                                            `/road-dispatch/arrivals/${row.id}`,
                                        ),
                                },
                                {
                                    label: 'Unload',
                                    onClick: (row) =>
                                        router.visit(
                                            `/road-dispatch/arrivals/${row.id}/unload`,
                                        ),
                                },
                            ]}
                            renderCell={(columnId, value, row) => {
                                if (columnId === 'siding_code') {
                                    return row.siding_code && row.siding_name
                                        ? `${row.siding_code} (${row.siding_name})`
                                        : '—';
                                }
                                if (columnId === 'vehicle_number') {
                                    return row.vehicle_number ?? row.vehicle_id ?? '—';
                                }
                                if (columnId === 'status') {
                                    return <StatusPill status={row.status} />;
                                }
                                if (columnId === 'net_weight') {
                                    return (
                                        row.net_weight ??
                                        row.unloaded_quantity ??
                                        '—'
                                    );
                                }
                                return undefined;
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
