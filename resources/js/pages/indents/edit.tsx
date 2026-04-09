import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, FileText, Pencil } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Indent {
    id: number;
    indent_number: string | null;
    target_quantity_mt: string | number | null;
    allocated_quantity_mt: string | number | null;
    available_stock_mt: string | number | null;
    state: string;
    indent_date: string | null;
    indent_time: string | null;
    required_by_date: string | null;
    remarks: string | null;
    e_demand_reference_id: string | null;
    fnr_number: string | null;
    railway_reference_no: string | null;
    destination: string | null;
    expected_loading_date: string | null;
    demanded_stock: string | null;
    total_units: number | null;
    siding_id: number;
    /** Prefer app route for download (same as indent PDF / e-Demand slip) */
    indent_pdf_download_url?: string | null;
    indent_pdf_url?: string | null;
    indent_confirmation_pdf_url?: string | null;
    rake?: {
        id: number;
        rake_number: string | null;
        priority_number: number | null;
    } | null;
}

interface Props {
    indent: Indent;
    sidings: Siding[];
    currentStockMt?: number;
}

const INDENT_STATES: { value: string; label: string }[] = [
    { value: 'historical_import', label: 'Historical import' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const selectClassName = cn(
    'border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none',
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
);

function toDatetimeLocal(value: string | null | undefined): string {
    if (value == null || value === '') {
        return '';
    }
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        return '';
    }
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function dateOnly(value: string | null | undefined): string {
    if (value == null || value === '') {
        return '';
    }
    return value.slice(0, 10);
}

function numOrEmpty(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }
    return String(value);
}

export default function IndentsEdit({ indent, sidings, currentStockMt }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const stateOptions = INDENT_STATES;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'E-Demand', href: '/indents' },
        { title: indent.indent_number || 'E-Demand', href: `/indents/${indent.id}` },
        { title: 'Edit', href: `/indents/${indent.id}/edit` },
    ];

    const indentAtDefault =
        toDatetimeLocal(indent.indent_date) || toDatetimeLocal(indent.indent_time);

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = new FormData(form);
        formData.append('_method', 'PUT');
        router.post(`/indents/${indent.id}`, formData, {
            forceFormData: true,
        });
    };

    const indentPdfHref =
        indent.indent_pdf_download_url ??
        indent.indent_pdf_url ??
        indent.indent_confirmation_pdf_url ??
        null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit e-demand ${indent.indent_number || ''}`} />
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                            <Pencil className="size-6 text-muted-foreground" />
                            Edit e-demand
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {indent.indent_number
                                ? `Forwarding note / e-demand ${indent.indent_number}`
                                : 'Update e-demand details and quantities'}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        data-pan="indents-edit-cancel"
                        onClick={() => router.visit(`/indents/${indent.id}`)}
                    >
                        Back to e-demand
                    </Button>
                </div>

                {indent.rake != null && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Linked rake</CardTitle>
                            <CardDescription>
                                Rake Sq. number and priority (from e-Demand / indent
                                id). Edit the rake record to change the rake number.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-wrap items-end justify-between gap-4">
                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-muted-foreground">
                                        Rake number
                                    </dt>
                                    <dd className="font-medium">
                                        {indent.rake.rake_number ?? '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground">
                                        Priority number
                                    </dt>
                                    <dd className="font-medium">
                                        {indent.rake.priority_number ?? '—'}
                                    </dd>
                                </div>
                            </dl>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/rakes/${indent.rake.id}`}>
                                    Open rake
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <FileText className="size-4" />
                                Location & status
                            </CardTitle>
                            <CardDescription>
                                Siding, workflow state, and official indent number
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="siding_id">Siding *</Label>
                                <select
                                    id="siding_id"
                                    name="siding_id"
                                    required
                                    defaultValue={indent.siding_id}
                                    className={selectClassName}
                                >
                                    {sidings.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name} ({s.code})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors?.siding_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="state">State</Label>
                                <select
                                    id="state"
                                    name="state"
                                    defaultValue={indent.state}
                                    className={selectClassName}
                                >
                                    {stateOptions.map((s) => (
                                        <option key={s.value} value={s.value}>
                                            {s.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors?.state} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="indent_number">
                                    E-Demand / forwarding note number
                                </Label>
                                <Input
                                    id="indent_number"
                                    name="indent_number"
                                    defaultValue={indent.indent_number ?? ''}
                                    placeholder="e.g. 302.001"
                                />
                                <InputError message={errors?.indent_number} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Demand & quantities
                            </CardTitle>
                            <CardDescription>
                                Stock type, units, and metric tonnes (as on e-Demand slip)
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="grid gap-2 sm:col-span-2 lg:col-span-1">
                                <Label htmlFor="demanded_stock">
                                    Demanded stock (wagon / stock type)
                                </Label>
                                <Input
                                    id="demanded_stock"
                                    name="demanded_stock"
                                    defaultValue={indent.demanded_stock ?? ''}
                                    placeholder="e.g. BOBRN"
                                />
                                <InputError message={errors?.demanded_stock} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="total_units">Total units</Label>
                                <Input
                                    id="total_units"
                                    name="total_units"
                                    type="number"
                                    min={0}
                                    step={1}
                                    defaultValue={
                                        indent.total_units != null
                                            ? String(indent.total_units)
                                            : ''
                                    }
                                    placeholder="Wagons"
                                />
                                <InputError message={errors?.total_units} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="target_quantity_mt">
                                    Target quantity (MT)
                                </Label>
                                <Input
                                    id="target_quantity_mt"
                                    name="target_quantity_mt"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    defaultValue={numOrEmpty(
                                        indent.target_quantity_mt,
                                    )}
                                />
                                <InputError message={errors?.target_quantity_mt} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="allocated_quantity_mt">
                                    Allocated quantity (MT)
                                </Label>
                                <Input
                                    id="allocated_quantity_mt"
                                    name="allocated_quantity_mt"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    defaultValue={numOrEmpty(
                                        indent.allocated_quantity_mt,
                                    )}
                                />
                                <InputError message={errors?.allocated_quantity_mt} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="available_stock_mt">
                                    Available stock (MT)
                                </Label>
                                {typeof currentStockMt === 'number' && (
                                    <p className="text-xs text-muted-foreground">
                                        Current stock for siding: {currentStockMt.toFixed(2)} MT
                                    </p>
                                )}
                                <Input
                                    id="available_stock_mt"
                                    name="available_stock_mt"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    defaultValue={numOrEmpty(
                                        indent.available_stock_mt ??
                                            (typeof currentStockMt === 'number'
                                                ? currentStockMt
                                                : null),
                                    )}
                                />
                                <InputError message={errors?.available_stock_mt} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Railway references
                            </CardTitle>
                            <CardDescription>
                                e-Demand, FNR, priority / railway ref, and destination
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="e_demand_reference_id">
                                    e-Demand reference ID
                                </Label>
                                <Input
                                    id="e_demand_reference_id"
                                    name="e_demand_reference_id"
                                    defaultValue={
                                        indent.e_demand_reference_id ?? ''
                                    }
                                />
                                <InputError
                                    message={errors?.e_demand_reference_id}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="fnr_number">FNR number</Label>
                                <Input
                                    id="fnr_number"
                                    name="fnr_number"
                                    defaultValue={indent.fnr_number ?? ''}
                                />
                                <InputError message={errors?.fnr_number} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="railway_reference_no">
                                    Railway reference no. (priority class / number)
                                </Label>
                                <Input
                                    id="railway_reference_no"
                                    name="railway_reference_no"
                                    defaultValue={
                                        indent.railway_reference_no ?? ''
                                    }
                                />
                                <InputError
                                    message={errors?.railway_reference_no}
                                />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="destination">Destination</Label>
                                <Input
                                    id="destination"
                                    name="destination"
                                    defaultValue={indent.destination ?? ''}
                                    placeholder="e.g. power plant siding name"
                                />
                                <InputError message={errors?.destination} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Dates</CardTitle>
                            <CardDescription>
                                Demand / indent time and loading deadlines
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="indent_at">
                                    E-Demand date &amp; time (demand)
                                </Label>
                                <Input
                                    id="indent_at"
                                    name="indent_at"
                                    type="datetime-local"
                                    defaultValue={indentAtDefault}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Stored on both indent date and indent time fields.
                                </p>
                                <InputError message={errors?.indent_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="expected_loading_date">
                                    Expected loading date
                                </Label>
                                <Input
                                    id="expected_loading_date"
                                    name="expected_loading_date"
                                    type="date"
                                    defaultValue={dateOnly(
                                        indent.expected_loading_date,
                                    )}
                                />
                                <InputError
                                    message={errors?.expected_loading_date}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="required_by_date">
                                    Required by
                                </Label>
                                <Input
                                    id="required_by_date"
                                    name="required_by_date"
                                    type="datetime-local"
                                    defaultValue={toDatetimeLocal(
                                        indent.required_by_date,
                                    )}
                                />
                                <InputError
                                    message={errors?.required_by_date}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                E-Demand PDF & notes
                            </CardTitle>
                            <CardDescription>
                                The e-Demand confirmation is stored as the indent PDF.
                                Download it here; use E-Demand import to attach a new file.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label>E-Demand PDF (e-Demand confirmation)</Label>
                                {indentPdfHref ? (
                                    <a
                                        href={indentPdfHref}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        data-pan="indents-edit-download-pdf"
                                        className="inline-flex w-fit items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium ring-offset-background hover:bg-accent hover:text-accent-foreground"
                                    >
                                        <Download className="size-4" />
                                        Download PDF
                                    </a>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No indent PDF attached. Import an e-Demand slip from
                                        the E-Demand list to add one.
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="remarks">Remarks</Label>
                                <textarea
                                    id="remarks"
                                    name="remarks"
                                    rows={4}
                                    defaultValue={indent.remarks ?? ''}
                                    className={cn(
                                        'border-input bg-background placeholder:text-muted-foreground min-h-[100px] w-full rounded-md border px-3 py-2 text-sm shadow-xs',
                                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] outline-none',
                                    )}
                                />
                                <InputError message={errors?.remarks} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" data-pan="indents-edit-submit">
                            Save changes
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            data-pan="indents-edit-cancel"
                            onClick={() => router.visit(`/indents/${indent.id}`)}
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
