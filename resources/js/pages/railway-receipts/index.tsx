import { DataTable } from 'laravel-data-table';
import type { DataTableResponse } from 'laravel-data-table';
import { GlossaryTerm } from '@/components/glossary-term';
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
import { FileText } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface RrDocumentRow {
    id: number;
    rake_id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    rake_number: string | null;
    siding_name: string | null;
}

interface Props {
    tableData: DataTableResponse<RrDocumentRow>;
    sidings: Siding[];
}

export default function RailwayReceiptsIndex({ tableData }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Railway Receipts', href: '/railway-receipts' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Railway Receipts" />
            <div className="space-y-6">
                <Heading
                    title="Railway Receipts"
                    description="RR documents and receipts by rake"
                />
                <div className="flex flex-wrap items-center gap-2">
                    <Link href="/railway-receipts/create">
                        <Button>
                            <FileText className="mr-2 size-4" />
                            Add RR document
                        </Button>
                    </Link>
                </div>
                <RrmcsGuidance
                    title="What this section is for"
                    before="RR documents filed in physical folders; FNR, freight, and wagon details copied manually into Excel."
                    after="Upload RR PDF, auto-parsed into structured data — FNR, freight, charges, wagon table all searchable."
                />
                <Card>
                    <CardHeader>
                        <CardTitle>RR documents</CardTitle>
                        <CardDescription>
                            Filter by siding or rake
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable<RrDocumentRow>
                            tableData={tableData}
                            tableName="railway-receipts"
                            actions={[
                                {
                                    label: 'View',
                                    onClick: (row) =>
                                        router.visit(
                                            `/railway-receipts/${row.id}`,
                                        ),
                                },
                            ]}
                            renderCell={(columnId, _value, row) => {
                                if (columnId === 'rake_number') {
                                    return row.rake_number ?? '-';
                                }
                                if (columnId === 'siding_name') {
                                    return row.siding_name ?? '-';
                                }
                                if (columnId === 'rr_weight_mt') {
                                    return row.rr_weight_mt ?? '-';
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
