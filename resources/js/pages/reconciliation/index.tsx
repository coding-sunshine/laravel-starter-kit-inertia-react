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
import { Head, Link, router } from '@inertiajs/react';
import { Scale } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface ReconciliationRow {
    id: number;
    rake_number: string;
    siding_name: string | null;
    overall_status: string;
}

interface Props {
    tableData: DataTableResponse<ReconciliationRow>;
    summary: { pending: number };
    sidings: Siding[];
}

export default function ReconciliationIndex({
    tableData,
    summary,
}: Props) {
    const matched = tableData.data.filter(
        (r) => r.overall_status === 'MATCH',
    ).length;
    const mismatched = tableData.data.filter((r) =>
        ['MINOR_DIFF', 'MAJOR_DIFF'].includes(r.overall_status),
    ).length;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Reconciliation', href: '/reconciliation' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reconciliation" />
            <div className="space-y-6">
                <Heading
                    title="Five-point reconciliation"
                    description="Mine vs Siding, Siding vs Rake, Rake vs Weighment, Weighment vs RR, RR vs Power Plant"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/reconciliation/power-plant-receipts">
                        <Button variant="outline">Power plant receipts</Button>
                    </Link>
                    <Link href="/reconciliation/power-plant-receipts/create">
                        <Button>Add power plant receipt</Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="Cross-checking RR against loading records and power-plant receipts done in Excel with vlookup, taking hours."
                    after="One-click reconciliation matching RR wagon data against loading records automatically."
                />
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                Pending (no successful rake weighment)
                            </CardDescription>
                            <CardTitle className="text-lg">
                                {summary.pending}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Matched this page</CardDescription>
                            <CardTitle className="text-lg">
                                {matched}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                Mismatched this page
                            </CardDescription>
                            <CardTitle className="text-lg">
                                {mismatched}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Rakes with successful weighment</CardTitle>
                        <CardDescription>Filter by siding</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<ReconciliationRow>
                            tableData={tableData}
                            tableName="reconciliation"
                            actions={[
                                {
                                    label: 'Detail',
                                    onClick: (row) =>
                                        router.visit(
                                            `/reconciliation/${row.id}`,
                                        ),
                                },
                            ]}
                            renderCell={(columnId, _value, row) => {
                                if (columnId === 'siding_name') {
                                    return row.siding_name ?? '—';
                                }
                                if (columnId === 'overall_status') {
                                    return (
                                        <span
                                            className={
                                                row.overall_status ===
                                                'MAJOR_DIFF'
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : row.overall_status ===
                                                        'MINOR_DIFF'
                                                      ? 'text-amber-600 dark:text-amber-400'
                                                      : ''
                                            }
                                        >
                                            {row.overall_status}
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
