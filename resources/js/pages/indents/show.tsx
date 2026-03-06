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
import { Download, FileText, Train, Plus } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Rake {
    id: number;
    rake_number: string;
    state: string;
}

interface Indent {
    id: number;
    siding_id: number;
    indent_number: string | null;
    demanded_stock: string | null;
    total_units: number | null;
    target_quantity_mt: string | number | null;
    allocated_quantity_mt: string | number | null;
    available_stock_mt: string | number | null;
    indent_date: string | null;
    indent_time: string | null;
    expected_loading_date: string | null;
    required_by_date: string | null;
    railway_reference_no: string | null;
    e_demand_reference_id: string | null;
    fnr_number: string | null;
    state: string;
    remarks: string | null;
    created_at: string;
    updated_at: string;
    indent_confirmation_pdf_url?: string | null;
    indent_pdf_url?: string | null;
    /** URL to download PDF via app route (prefer over direct media URL) */
    indent_pdf_download_url?: string | null;
    siding?: Siding | null;
}

interface Props {
    indent: Indent;
    rake?: Rake | null;
}

export default function IndentsShow({ indent, rake }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Indents', href: '/indents' },
        { title: indent.indent_number || 'N/A', href: `/indents/${indent.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Indent ${indent.indent_number || 'N/A'}`} />
            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h2 className="text-lg font-medium">
                        Indent {indent.indent_number || 'N/A'}
                    </h2>
                    <div className="flex gap-2">
                        {!rake ? (
                            <Link href={`/indents/${indent.id}/create-rake`}>
                                <Button variant="default" size="sm">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create Rake
                                </Button>
                            </Link>
                        ) : (
                            <Link href={`/rakes/${rake.id}`}>
                                <Button variant="outline" size="sm">
                                    <Train className="mr-2 h-4 w-4" />
                                    View Rake
                                </Button>
                            </Link>
                        )}
                        <Link href={`/indents/${indent.id}/edit`}>
                            <Button variant="outline" size="sm">
                                Edit
                            </Button>
                        </Link>
                    </div>
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
                                    {indent.indent_number ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Siding (Station From)
                                </dt>
                                <dd>
                                    {indent.siding?.name ?? '—'}{' '}
                                    {indent.siding?.code != null && (
                                        <span className="text-muted-foreground">
                                            ({indent.siding.code})
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">State</dt>
                                <dd className="capitalize">
                                    {indent.state ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Demanded stock
                                </dt>
                                <dd>{indent.demanded_stock ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Total units
                                </dt>
                                <dd>
                                    {indent.total_units != null
                                        ? indent.total_units
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Target quantity (MT)
                                </dt>
                                <dd>
                                    {indent.target_quantity_mt != null
                                        ? indent.target_quantity_mt
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Allocated quantity (MT)
                                </dt>
                                <dd>
                                    {indent.allocated_quantity_mt != null
                                        ? indent.allocated_quantity_mt
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Available stock (MT)
                                </dt>
                                <dd>
                                    {indent.available_stock_mt != null
                                        ? indent.available_stock_mt
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Indent date
                                </dt>
                                <dd>
                                    {indent.indent_date
                                        ? new Date(
                                              indent.indent_date,
                                          ).toLocaleString()
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Indent time
                                </dt>
                                <dd>
                                    {indent.indent_time
                                        ? new Date(
                                              indent.indent_time,
                                          ).toLocaleTimeString()
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Expected loading date
                                </dt>
                                <dd>
                                    {indent.expected_loading_date
                                        ? new Date(
                                              indent.expected_loading_date,
                                          ).toLocaleDateString()
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Required by date
                                </dt>
                                <dd>
                                    {indent.required_by_date
                                        ? new Date(
                                              indent.required_by_date,
                                          ).toLocaleString()
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Railway reference no.
                                </dt>
                                <dd>
                                    {indent.railway_reference_no ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    e-Demand reference ID
                                </dt>
                                <dd>
                                    {indent.e_demand_reference_id ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    FNR number
                                </dt>
                                <dd>{indent.fnr_number ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Created at
                                </dt>
                                <dd>
                                    {indent.created_at
                                        ? new Date(
                                              indent.created_at,
                                          ).toLocaleString()
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Updated at
                                </dt>
                                <dd>
                                    {indent.updated_at
                                        ? new Date(
                                              indent.updated_at,
                                          ).toLocaleString()
                                        : '—'}
                                </dd>
                            </div>
                        </dl>
                        {indent.remarks != null && indent.remarks !== '' && (
                            <div>
                                <dt className="text-muted-foreground">
                                    Remarks
                                </dt>
                                <p className="mt-1 text-sm whitespace-pre-wrap">
                                    {indent.remarks}
                                </p>
                            </div>
                        )}
                        {(indent.indent_pdf_download_url ??
                            indent.indent_pdf_url ??
                            indent.indent_confirmation_pdf_url) && (
                            <div className="mt-4">
                                <a
                                    href={
                                        indent.indent_pdf_download_url ??
                                        indent.indent_pdf_url ??
                                        indent.indent_confirmation_pdf_url ??
                                        '#'
                                    }
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium ring-offset-background hover:bg-accent hover:text-accent-foreground"
                                >
                                    <Download className="size-4" />
                                    Download PDF
                                </a>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {rake && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Train className="size-5" />
                                Associated Rake
                            </CardTitle>
                            <CardDescription>
                                Rake created from this indent
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Rake number
                                    </dt>
                                    <dd className="font-medium">
                                        {rake.rake_number}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">State</dt>
                                    <dd className="capitalize">{rake.state}</dd>
                                </div>
                            </dl>
                            <div className="mt-4">
                                <Link href={`/rakes/${rake.id}`}>
                                    <Button variant="outline" size="sm">
                                        <Train className="mr-2 h-4 w-4" />
                                        View Rake Details
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
