import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import { GlossaryTerm } from '@/components/glossary-term';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
import { StatusPill } from '@/components/status-pill';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Train } from 'lucide-react';

interface RakeRow {
    id: number;
    rake_number: string;
    rake_type: string | null;
    wagon_count: number;
    state: string;
    placement_time: string | null;
    dispatch_time: string | null;
    siding_code: string | null;
    siding_name: string | null;
}

interface Props {
    tableData: DataTableResponse<RakeRow>;
}

export default function RakesIndex({ tableData }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Rakes', href: '/rakes' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rakes" />

            <div className="space-y-6">
                <Heading
                    title="Railway Rakes"
                    description="Manage railway rakes and wagons for the RRMCS system"
                />

                <RrmcsGuidance
                    title="What this section is for"
                    before="Rake status and 3-hour loading window tracked in Excel and stopwatch; demurrage and penalties found only after Railway Receipt (RR) arrives."
                    after="Rake list with live demurrage countdown; alerts at 60 min (amber), 30 min (red), 0 min (critical). Overload detection during weighment—24+ hours before RR."
                />

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Train className="h-5 w-5" />
                            Rakes
                        </CardTitle>
                        <CardDescription>
                            All railway rakes you have access to
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<RakeRow>
                            tableData={tableData}
                            tableName="rakes"
                            actions={[
                                {
                                    label: 'View',
                                    onClick: (row) =>
                                        router.visit(`/rakes/${row.id}`),
                                },
                            ]}
                            renderCell={(columnId, value, row) => {
                                if (columnId === 'siding_code') {
                                    return row.siding_code && row.siding_name
                                        ? `${row.siding_code} (${row.siding_name})`
                                        : '—';
                                }
                                if (columnId === 'state') {
                                    return <StatusPill status={row.state} />;
                                }
                                if (columnId === 'placement_time') {
                                    return row.placement_time
                                        ? new Date(row.placement_time).toLocaleString()
                                        : '—';
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
