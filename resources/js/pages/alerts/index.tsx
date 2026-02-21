import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import Heading from '@/components/heading';
import { RrmcsGuidance } from '@/components/rrmcs-guidance';
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
import { Head, router } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface AlertRow {
    id: number;
    type: string;
    title: string;
    body: string | null;
    severity: string;
    status: string;
    created_at: string;
    rake_id: number | null;
    siding_id: number | null;
    rake_number: string | null;
    siding_name: string | null;
}

interface Props {
    tableData: DataTableResponse<AlertRow>;
    sidings: Siding[];
}

export default function AlertsIndex({ tableData }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Alerts', href: '/alerts' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alerts" />
            <div className="space-y-6">
                <Heading
                    title="Alerts"
                    description="Demurrage, overload, and RR mismatch alerts"
                />
                <RrmcsGuidance
                    title="What this section is for"
                    before="No formal alert system; demurrage discovered only when RR arrived (24+ hours late)."
                    after="Automated alerts at 60/30/0 minutes remaining; escalation by role (operator → in-charge → management)."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>Alert list</CardTitle>
                        <CardDescription>
                            Filter by siding or status
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<AlertRow>
                            tableData={tableData}
                            tableName="alerts"
                            actions={[
                                {
                                    label: 'Resolve',
                                    onClick: (row) =>
                                        router.put(
                                            `/alerts/${row.id}/resolve`,
                                            { redirect: '/alerts' },
                                        ),
                                    visible: (row) => row.status === 'active',
                                },
                            ]}
                            renderCell={(columnId, _value, row) => {
                                if (columnId === 'rake_number') {
                                    return row.rake_number
                                        ? row.rake_number
                                        : row.siding_name ?? '—';
                                }
                                if (columnId === 'severity') {
                                    return (
                                        <span className="capitalize">
                                            {row.severity}
                                        </span>
                                    );
                                }
                                if (columnId === 'status') {
                                    return (
                                        <span className="capitalize">
                                            {row.status}
                                        </span>
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
