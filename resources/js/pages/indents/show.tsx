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
import { Head, Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Indent {
    id: number;
    indent_number: string;
    target_quantity_mt: string;
    allocated_quantity_mt: string;
    state: string;
    indent_date: string;
    required_by_date: string | null;
    remarks: string | null;
    e_demand_reference_id: string | null;
    fnr_number: string | null;
    indent_confirmation_pdf_url?: string | null;
    siding?: Siding | null;
}

interface Props {
    indent: Indent;
}

export default function IndentsShow({ indent }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Indents', href: '/indents' },
        { title: indent.indent_number, href: `/indents/${indent.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Indent ${indent.indent_number}`} />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">
                        Indent {indent.indent_number}
                    </h2>
                    <Link href={`/indents/${indent.id}/edit`}>
                        <Button variant="outline" size="sm">
                            Edit
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="size-5" />
                            Details
                        </CardTitle>
                        <CardDescription>
                            Indent and e-Demand reference information
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-muted-foreground">
                                    Indent number
                                </dt>
                                <dd className="font-medium">
                                    {indent.indent_number}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Siding
                                </dt>
                                <dd>
                                    {indent.siding?.name ?? '—'}{' '}
                                    {indent.siding?.code && (
                                        <span className="text-muted-foreground">
                                            ({indent.siding.code})
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Target quantity (MT)
                                </dt>
                                <dd>{indent.target_quantity_mt}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Allocated (MT)
                                </dt>
                                <dd>{indent.allocated_quantity_mt}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">State</dt>
                                <dd className="capitalize">{indent.state}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Indent date
                                </dt>
                                <dd>
                                    {new Date(
                                        indent.indent_date,
                                    ).toLocaleDateString()}
                                </dd>
                            </div>
                            {indent.required_by_date && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Required by
                                    </dt>
                                    <dd>
                                        {new Date(
                                            indent.required_by_date,
                                        ).toLocaleDateString()}
                                    </dd>
                                </div>
                            )}
                            {indent.e_demand_reference_id && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        e-Demand reference ID
                                    </dt>
                                    <dd>{indent.e_demand_reference_id}</dd>
                                </div>
                            )}
                            {indent.fnr_number && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        FNR number
                                    </dt>
                                    <dd>{indent.fnr_number}</dd>
                                </div>
                            )}
                        </dl>
                        {indent.indent_confirmation_pdf_url && (
                            <p>
                                <a
                                    href={indent.indent_confirmation_pdf_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-primary underline underline-offset-2"
                                >
                                    View confirmation (PDF)
                                </a>
                            </p>
                        )}
                        {indent.remarks && (
                            <div>
                                <dt className="text-muted-foreground">
                                    Remarks
                                </dt>
                                <p className="mt-1 text-sm">
                                    {indent.remarks}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
