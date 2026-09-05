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
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Train, AlertCircle, CheckCircle } from 'lucide-react';
import { useMemo } from 'react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface PowerPlantOption {
    name: string;
    code: string;
}

interface Indent {
    id: number;
    indent_number: string | null;
    state: string;
    expected_loading_date: string | null;
    demanded_stock: string | null;
    total_units: string | null;
    siding?: Siding | null;
    siding_id?: number | null;
}

interface Props {
    indent: Indent;
    next_priority_number: number;
    power_plants: PowerPlantOption[];
    prefill_destination_code?: string | null;
    flash?: {
        success?: string;
        error?: string;
    };
}

interface FormErrors {
    [key: string]: string | string[];
}

type InertiaPageProps = {
    errors?: FormErrors;
};

export default function CreateRakeFromIndent({
    indent,
    next_priority_number,
    power_plants,
    prefill_destination_code,
    flash,
}: Props) {
    const page = usePage<InertiaPageProps>();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'E-Demand', href: '/indents' },
        { title: indent.indent_number || 'N/A', href: `/indents/${indent.id}` },
        { title: 'Create Rake', href: `/indents/${indent.id}/create-rake` },
    ];

    const totalUnits = indent.total_units;

    const { data, setData, post, processing, errors: formErrors } = useForm({
        rake_number: '',
        rake_serial_number: '',
        rake_priority_number: String(next_priority_number),
        loading_date: indent.expected_loading_date
            ? new Date(indent.expected_loading_date).toISOString().slice(0, 10)
            : '',
        rake_type: indent.demanded_stock || '',
        wagon_count: totalUnits != null ? String(totalUnits) : '',
        destination_code: prefill_destination_code ?? '',
        rr_expected_date: '',
        placement_time: '',
        remarks: '',
    });

    /**
     * Validation errors can arrive two ways:
     * - 422 Inertia response: populated on `useForm().errors`
     * - redirect()->back()->withErrors(): only on shared `page.props.errors` (useForm resets on new visit)
     */
    const errors = useMemo((): FormErrors => {
        const shared = page.props.errors ?? {};

        return { ...shared, ...formErrors };
    }, [page.props.errors, formErrors]);

    const hasErrors = Object.keys(errors).length > 0;

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(`/indents/${indent.id}/store-rake`, {
            preserveScroll: true,
        });
    }

    const getErrorMessage = (fieldErrors: string | string[] | undefined): string => {
        if (!fieldErrors) {
            return '';
        }
        if (Array.isArray(fieldErrors)) {
            return fieldErrors[0] ?? '';
        }

        return fieldErrors;
    };

    const hasError = (fieldName: string): boolean => fieldName in errors;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Create Rake for ${indent.indent_number || 'N/A'}`} />

            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={`/indents/${indent.id}`}>
                        <Button variant="outline" size="sm">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Back to Indent
                        </Button>
                    </Link>
                    <h2 className="text-lg font-medium">
                        Create Rake from Indent {indent.indent_number || 'N/A'}
                    </h2>
                </div>

                {/* Flash Messages */}
                {flash?.error && (
                    <Card className="border-destructive bg-destructive/5">
                        <CardContent className="flex gap-4 pt-6">
                            <AlertCircle className="h-5 w-5 text-destructive flex-shrink-0 mt-0.5" />
                            <div>
                                <h3 className="font-semibold text-destructive">Error</h3>
                                <p className="text-sm text-destructive mt-1">{flash.error}</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {flash?.success && (
                    <Card className="border-green-600 bg-green-50">
                        <CardContent className="flex gap-4 pt-6">
                            <CheckCircle className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <h3 className="font-semibold text-green-600">Success</h3>
                                <p className="text-sm text-green-600 mt-1">{flash.success}</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Validation Errors Summary */}
                {hasErrors && (
                    <Card className="border-destructive bg-destructive/5">
                        <CardContent className="pt-6">
                            <div className="flex gap-4">
                                <AlertCircle className="h-5 w-5 text-destructive flex-shrink-0 mt-0.5" />
                                <div className="flex-1">
                                    <h3 className="font-semibold text-destructive mb-3">
                                        Please fix the {Object.keys(errors).length} error(s) below
                                    </h3>
                                    <ul className="space-y-2 text-sm">
                                        {Object.entries(errors).map(([field, message]) => (
                                            <li key={field} className="flex items-start gap-2 text-destructive">
                                                <span className="text-lg leading-none mt-0.5">•</span>
                                                <span>
                                                    <strong className="capitalize">
                                                        {field.replace(/_/g, ' ')}:
                                                    </strong>{' '}
                                                    {getErrorMessage(message)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Train className="h-5 w-5" />
                            Rake Information
                        </CardTitle>
                        <CardDescription>
                            Create a railway rake based on the completed indent. Common fields are pre-filled from the indent.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-4 sm:grid-cols-2">
                                {/* Indent Number - Read Only */}
                                <div>
                                    <Label htmlFor="indent_number">Indent Number</Label>
                                    <Input
                                        id="indent_number"
                                        value={indent.indent_number || 'N/A'}
                                        disabled
                                        className="bg-muted"
                                    />
                                </div>

                                {/* Rake sequence */}
                                <div>
                                    <Label htmlFor="rake_number" className="flex items-center gap-1">
                                        Rake sequence *
                                        {hasError('rake_number') && (
                                            <span className="text-destructive text-lg leading-none">*</span>
                                        )}
                                    </Label>
                                    <Input
                                        id="rake_number"
                                        required
                                        value={data.rake_number}
                                        onChange={(e) =>
                                            setData('rake_number', e.target.value)
                                        }
                                        placeholder="e.g. 1"
                                        className={`${
                                            hasError('rake_number')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        }`}
                                    />
                                    {hasError('rake_number') && (
                                        <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                            <span className="text-lg leading-none">⚠</span>
                                            {getErrorMessage(errors.rake_number)}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label
                                        htmlFor="rake_serial_number"
                                        className="flex flex-wrap items-center gap-1"
                                    >
                                        <span>Rake number</span>
                                        <span className="text-muted-foreground font-normal">
                                            (optional)
                                        </span>
                                        {hasError('rake_serial_number') && (
                                            <span className="text-destructive text-lg leading-none">
                                                *
                                            </span>
                                        )}
                                    </Label>
                                    <Input
                                        id="rake_serial_number"
                                        name="rake_serial_number"
                                        value={data.rake_serial_number}
                                        onChange={(e) =>
                                            setData(
                                                'rake_serial_number',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Leave blank if not applicable"
                                        className={`${
                                            hasError('rake_serial_number')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        }`}
                                    />
                                    {hasError('rake_serial_number') && (
                                        <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                            <span className="text-lg leading-none">⚠</span>
                                            {getErrorMessage(
                                                errors.rake_serial_number,
                                            )}
                                        </p>
                                    )}
                                </div>

                                {/* Rake Priority Number */}
                                <div>
                                    <Label htmlFor="rake_priority_number" className="flex items-center gap-1">
                                        Rake Priority Number
                                        {hasError('rake_priority_number') && (
                                            <span className="text-destructive text-lg leading-none">*</span>
                                        )}
                                    </Label>
                                    <Input
                                        id="rake_priority_number"
                                        type="number"
                                        min="0"
                                        value={data.rake_priority_number}
                                        onChange={(e) => setData('rake_priority_number', e.target.value)}
                                        placeholder="Priority order"
                                        className={`${
                                            hasError('rake_priority_number')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        }`}
                                    />
                                    {hasError('rake_priority_number') && (
                                        <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                            <span className="text-lg leading-none">⚠</span>
                                            {getErrorMessage(errors.rake_priority_number)}
                                        </p>
                                    )}
                                </div>

                                {/* Loading Date */}
                                <div>
                                    <Label htmlFor="loading_date" className="flex items-center gap-1">
                                        Loading Date
                                        {hasError('loading_date') && (
                                            <span className="text-destructive text-lg leading-none">*</span>
                                        )}
                                    </Label>
                                    <Input
                                        id="loading_date"
                                        type="date"
                                        value={data.loading_date}
                                        onChange={(e) => setData('loading_date', e.target.value)}
                                        className={`${
                                            hasError('loading_date')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        }`}
                                    />
                                    {hasError('loading_date') && (
                                        <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                            <span className="text-lg leading-none">⚠</span>
                                            {getErrorMessage(errors.loading_date)}
                                        </p>
                                    )}
                                </div>

                                {/* Siding - Read Only */}
                                <div>
                                    <Label htmlFor="siding">Siding</Label>
                                    <Input
                                        id="siding"
                                        value={indent.siding ? `${indent.siding.name} (${indent.siding.code})` : '—'}
                                        disabled
                                        className="bg-muted"
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="destination_code" className="flex items-center gap-1">
                                        Destination
                                        {hasError('destination_code') && (
                                            <span className="text-destructive text-lg leading-none">*</span>
                                        )}
                                    </Label>
                                    <select
                                        id="destination_code"
                                        value={data.destination_code}
                                        onChange={(e) => setData('destination_code', e.target.value)}
                                        required
                                        className={`h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 ${
                                            hasError('destination_code')
                                                ? 'border-destructive focus-visible:ring-destructive'
                                                : ''
                                        }`}
                                    >
                                        <option value="">Select power plant</option>
                                        {power_plants.map((p) => (
                                            <option key={p.code} value={p.code}>
                                                {p.name} ({p.code})
                                            </option>
                                        ))}
                                    </select>
                                    {hasError('destination_code') && (
                                        <p className="mt-1.5 flex items-center gap-1.5 text-sm text-destructive">
                                            <span className="text-lg leading-none">⚠</span>
                                            {getErrorMessage(errors.destination_code)}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Rake Details Section */}
                            <div className="border-t pt-6">
                                <h3 className="text-lg font-medium mb-4">Rake Details</h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {/* Rake Type */}
                                    <div>
                                        <Label htmlFor="rake_type" className="flex items-center gap-1">
                                            Rake Type
                                            {hasError('rake_type') && (
                                                <span className="text-destructive text-lg leading-none">*</span>
                                            )}
                                        </Label>
                                        <Input
                                            id="rake_type"
                                            value={data.rake_type}
                                            onChange={(e) => setData('rake_type', e.target.value)}
                                            placeholder={indent.demanded_stock || 'e.g., BOBRN, BOXN'}
                                            className={`${
                                                hasError('rake_type')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : ''
                                            }`}
                                        />
                                        {hasError('rake_type') && (
                                            <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                                <span className="text-lg leading-none">⚠</span>
                                                {getErrorMessage(errors.rake_type)}
                                            </p>
                                        )}
                                    </div>

                                    {/* Wagon Count */}
                                    <div>
                                        <Label htmlFor="wagon_count" className="flex items-center gap-1">
                                            Wagon Count
                                            {hasError('wagon_count') && (
                                                <span className="text-destructive text-lg leading-none">*</span>
                                            )}
                                        </Label>
                                        <Input
                                            id="wagon_count"
                                            type="number"
                                            min="0"
                                            value={data.wagon_count}
                                            onChange={(e) => setData('wagon_count', e.target.value)}
                                            placeholder="Number of wagons"
                                            className={`${
                                                hasError('wagon_count')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : ''
                                            }`}
                                        />
                                        {hasError('wagon_count') && (
                                            <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                                <span className="text-lg leading-none">⚠</span>
                                                {getErrorMessage(errors.wagon_count)}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Optional Fields Section */}
                            <div className="border-t pt-6">
                                <h3 className="text-lg font-medium mb-4">Additional Information</h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {/* RR Expected Date */}
                                    <div>
                                        <Label htmlFor="rr_expected_date" className="flex items-center gap-1">
                                            RR Expected Date
                                            {hasError('rr_expected_date') && (
                                                <span className="text-destructive text-lg leading-none">*</span>
                                            )}
                                        </Label>
                                        <Input
                                            id="rr_expected_date"
                                            type="date"
                                            value={data.rr_expected_date}
                                            onChange={(e) => setData('rr_expected_date', e.target.value)}
                                            className={`${
                                                hasError('rr_expected_date')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : ''
                                            }`}
                                        />
                                        {hasError('rr_expected_date') && (
                                            <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                                <span className="text-lg leading-none">⚠</span>
                                                {getErrorMessage(errors.rr_expected_date)}
                                            </p>
                                        )}
                                    </div>

                                    {/* Placement Time */}
                                    <div>
                                        <Label htmlFor="placement_time" className="flex items-center gap-1">
                                            Placement Time
                                            {hasError('placement_time') && (
                                                <span className="text-destructive text-lg leading-none">*</span>
                                            )}
                                        </Label>
                                        <Input
                                            id="placement_time"
                                            type="date"
                                            value={data.placement_time}
                                            onChange={(e) => setData('placement_time', e.target.value)}
                                            className={`${
                                                hasError('placement_time')
                                                    ? 'border-destructive focus-visible:ring-destructive'
                                                    : ''
                                            }`}
                                        />
                                        {hasError('placement_time') && (
                                            <p className="text-sm text-destructive mt-1.5 flex items-center gap-1.5">
                                                <span className="text-lg leading-none">⚠</span>
                                                {getErrorMessage(errors.placement_time)}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Remarks */}
                            <div>
                                <Label htmlFor="remarks" className="flex items-center gap-1">
                                    Remarks
                                    {hasError('remarks') && (
                                        <span className="text-destructive text-lg leading-none">*</span>
                                    )}
                                </Label>
                                <textarea
                                    id="remarks"
                                    value={data.remarks}
                                    onChange={(e) => setData('remarks', e.target.value)}
                                    placeholder="Additional notes about this rake (max 1000 characters)"
                                    className={`min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${
                                        hasError('remarks')
                                            ? 'border-destructive focus-visible:ring-destructive'
                                            : ''
                                    }`}
                                    maxLength={1000}
                                />
                                <div className="flex items-center justify-between mt-1.5">
                                    {hasError('remarks') && (
                                        <p className="text-sm text-destructive flex items-center gap-1.5">
                                            <span className="text-lg leading-none">⚠</span>
                                            {getErrorMessage(errors.remarks)}
                                        </p>
                                    )}
                                    <span className={`text-xs ml-auto ${
                                        data.remarks.length > 900
                                            ? 'text-yellow-600'
                                            : 'text-muted-foreground'
                                    }`}>
                                        {data.remarks.length}/1000
                                    </span>
                                </div>
                            </div>

                            {/* Form Actions */}
                            <div className="flex justify-end gap-4 pt-6 border-t">
                                <Link href={`/indents/${indent.id}`}>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating Rake...' : 'Create Rake'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}