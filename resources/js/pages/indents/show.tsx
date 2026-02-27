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
import { FileText, Train, Plus } from 'lucide-react';

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
    indent_number: string | null;
    target_quantity_mt: string | null;
    allocated_quantity_mt: string;
    state: string;
    indent_date: string | null;
    required_by_date: string | null;
    remarks: string | null;
    e_demand_reference_id: string | null;
    fnr_number: string | null;
    expected_loading_date: string | null;
    demanded_stock: string | null;
    total_units: string | null;
    indent_confirmation_pdf_url?: string | null;
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
                                    {indent.indent_number || '—'}
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
                                <dt className="text-muted-foreground">State</dt>
                                <dd className="capitalize">{indent.state}</dd>
                            </div>
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
                            {indent.expected_loading_date && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Expected Loading Date
                                    </dt>
                                    <dd>
                                        {new Date(
                                            indent.expected_loading_date,
                                        ).toLocaleDateString()}
                                    </dd>
                                </div>
                            )}
                            {indent.demanded_stock && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Rake Type
                                    </dt>
                                    <dd>{indent.demanded_stock}</dd>
                                </div>
                            )}
                            {indent.total_units && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Total Wagons
                                    </dt>
                                    <dd>{indent.total_units}</dd>
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
                                <p className="mt-1 text-sm">{indent.remarks}</p>
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
