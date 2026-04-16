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
import { postFormDataExpectJson } from '@/lib/laravel-json-fetch';
import { cn } from '@/lib/utils';
import type { InertiaFormProps } from '@inertiajs/react';
import { router, useForm } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';
import { useEffect, useLayoutEffect, useMemo, useState } from 'react';

export interface SidingOption {
    id: number;
    name: string;
    code: string;
}

export interface PowerPlantOption {
    name: string;
    code: string;
}

export interface IndentCreatePrefill {
    siding_id?: number;
    rake_number?: string;
    rake_priority_number?: number;
    expected_loading_date?: string | null;
    demanded_stock?: string;
    total_units?: number;
    target_quantity_mt?: number;
    destination?: string;
    indent_at?: string;
    indent_number?: string;
    e_demand_reference_id?: string;
    fnr_number?: string;
    remarks?: string;
}

interface FormFields {
    siding_id: string;
    rake_number: string;
    rake_priority_number: string;
    expected_loading_date: string;
    demanded_stock: string;
    total_units: string;
    target_quantity_mt: string;
    destination: string;
    indent_at: string;
    indent_number: string;
    e_demand_reference_id: string;
    fnr_number: string;
    remarks: string;
    pdf: File | null;
}

function emptyFields(): FormFields {
    return {
        siding_id: '',
        rake_number: '',
        rake_priority_number: '',
        expected_loading_date: '',
        demanded_stock: '',
        total_units: '',
        target_quantity_mt: '',
        destination: '',
        indent_at: '',
        indent_number: '',
        e_demand_reference_id: '',
        fnr_number: '',
        remarks: '',
        pdf: null,
    };
}

/** Visual order for scrolling to the first field with a validation error. */
const INDENT_FORM_SCROLL_ORDER: (keyof FormFields)[] = [
    'siding_id',
    'rake_number',
    'rake_priority_number',
    'expected_loading_date',
    'demanded_stock',
    'total_units',
    'target_quantity_mt',
    'destination',
    'indent_at',
    'indent_number',
    'e_demand_reference_id',
    'fnr_number',
    'pdf',
    'remarks',
];

const KNOWN_FORM_FIELD_KEYS = new Set(
    Object.keys(emptyFields()) as (keyof FormFields)[],
);

function appendIndentStoreFormData(data: FormFields, fd: FormData): void {
    fd.append('siding_id', data.siding_id);
    fd.append('rake_number', data.rake_number);
    fd.append('rake_priority_number', data.rake_priority_number);
    fd.append('expected_loading_date', data.expected_loading_date);
    fd.append('demanded_stock', data.demanded_stock);
    fd.append('total_units', data.total_units);
    fd.append('target_quantity_mt', data.target_quantity_mt);
    fd.append('destination', data.destination);
    fd.append('indent_at', data.indent_at);
    fd.append('indent_number', data.indent_number);
    fd.append('e_demand_reference_id', data.e_demand_reference_id);
    fd.append('fnr_number', data.fnr_number);
    fd.append('remarks', data.remarks);
    if (data.pdf) {
        fd.append('pdf', data.pdf);
    }
}

function firstValidationMessage(raw: unknown): string | undefined {
    if (typeof raw === 'string') {
        const t = raw.trim();
        return t === '' ? undefined : t;
    }
    if (Array.isArray(raw) && raw.length > 0) {
        const s = String(raw[0]).trim();

        return s === '' ? undefined : s;
    }
    if (
        raw !== null &&
        typeof raw === 'object' &&
        !Array.isArray(raw) &&
        'message' in raw
    ) {
        return firstValidationMessage(
            (raw as { message: unknown }).message,
        );
    }

    return undefined;
}

function normalizeLaravelTopMessage(body: Record<string, unknown>): string | null {
    const m = body.message;
    if (typeof m === 'string' && m.trim() !== '') {
        return m.trim();
    }
    if (Array.isArray(m) && m[0] !== undefined) {
        const s = String(m[0]).trim();

        return s === '' ? null : s;
    }

    return null;
}

/** Pull Laravel-style `errors` object from common JSON shapes. */
function getLaravelErrorsBag(body: unknown): Record<string, unknown> | null {
    if (body === null || typeof body !== 'object') {
        return null;
    }
    const o = body as Record<string, unknown>;
    const direct = o.errors;
    if (
        direct !== null &&
        typeof direct === 'object' &&
        !Array.isArray(direct)
    ) {
        return direct as Record<string, unknown>;
    }
    const data = o.data;
    if (data !== null && typeof data === 'object' && !Array.isArray(data)) {
        const nested = (data as Record<string, unknown>).errors;
        if (
            nested !== null &&
            typeof nested === 'object' &&
            !Array.isArray(nested)
        ) {
            return nested as Record<string, unknown>;
        }
    }

    return null;
}

/**
 * JSON:API / RFC7807 style:
 * `{ "errors": [ { "detail": "…", "source": { "pointer": "/rake_number" } } ] }`
 */
function parseJsonApiStyleErrorsArray(body: unknown): {
    fields: Partial<Record<keyof FormFields, string>>;
    orphans: string[];
} {
    const fields: Partial<Record<keyof FormFields, string>> = {};
    const orphans: string[] = [];

    if (body === null || typeof body !== 'object') {
        return { fields, orphans };
    }
    const o = body as Record<string, unknown>;
    const errs = o.errors;
    if (!Array.isArray(errs)) {
        return { fields, orphans };
    }

    for (const item of errs) {
        if (item === null || typeof item !== 'object') {
            continue;
        }
        const row = item as Record<string, unknown>;
        const detailRaw = row.detail ?? row.title;
        const detail =
            typeof detailRaw === 'string'
                ? detailRaw.trim()
                : firstValidationMessage(detailRaw);
        if (!detail) {
            continue;
        }

        let pointer: string | undefined;
        const source = row.source;
        if (
            source !== null &&
            typeof source === 'object' &&
            !Array.isArray(source)
        ) {
            const p = (source as Record<string, unknown>).pointer;
            if (typeof p === 'string') {
                pointer = p;
            }
        }

        let mappedKey: keyof FormFields | null = null;
        if (pointer !== undefined && pointer !== '') {
            const segments = pointer.split('/').filter(Boolean);
            const last = segments[segments.length - 1];
            if (
                last !== undefined &&
                KNOWN_FORM_FIELD_KEYS.has(last as keyof FormFields)
            ) {
                mappedKey = last as keyof FormFields;
            }
        }

        if (mappedKey !== null) {
            fields[mappedKey] = detail;
        } else {
            orphans.push(detail);
        }
    }

    return { fields, orphans };
}

/**
 * Laravel 422 JSON → field errors (known inputs) + optional banner for
 * attributes not on this form or when only a top-level message exists.
 */
function parseLaravel422Response(body: unknown): {
    fields: Partial<Record<keyof FormFields, string>>;
    banner: string | null;
} {
    const fields: Partial<Record<keyof FormFields, string>> = {};
    const orphanLines: string[] = [];

    const bag = getLaravelErrorsBag(body);
    if (bag) {
        for (const key of Object.keys(bag)) {
            const baseKey = key.includes('.')
                ? key.slice(0, key.indexOf('.'))
                : key;
            const msg = firstValidationMessage(bag[key]);
            if (msg === undefined) {
                continue;
            }
            if (KNOWN_FORM_FIELD_KEYS.has(baseKey as keyof FormFields)) {
                fields[baseKey as keyof FormFields] = msg;
            } else {
                orphanLines.push(msg);
            }
        }
    }

    const jsonApi = parseJsonApiStyleErrorsArray(body);
    for (const key of Object.keys(jsonApi.fields) as (keyof FormFields)[]) {
        const v = jsonApi.fields[key];
        if (v !== undefined && v !== '') {
            fields[key] = v;
        }
    }
    orphanLines.push(...jsonApi.orphans);

    let banner: string | null = null;
    if (orphanLines.length > 0) {
        banner = [...new Set(orphanLines)].join('\n');
    }

    const hasField = Object.keys(fields).length > 0;
    if (!hasField && body !== null && typeof body === 'object') {
        const top = normalizeLaravelTopMessage(body as Record<string, unknown>);
        if (top !== null) {
            banner = banner !== null ? `${top}\n${banner}` : top;
        }
    }

    const trimmed =
        banner !== null && banner.trim() !== '' ? banner.trim() : null;

    return { fields, banner: trimmed };
}

function mergeIndentFieldMessages(
    inertiaErrors: InertiaFormProps<FormFields>['errors'],
    overrides?: Partial<Record<keyof FormFields, string>>,
): Partial<Record<keyof FormFields, string>> {
    const keys = Object.keys(emptyFields()) as (keyof FormFields)[];
    const out: Partial<Record<keyof FormFields, string>> = {};
    for (const k of keys) {
        const o = overrides?.[k];
        if (o !== undefined && o !== '') {
            out[k] = o;
            continue;
        }
        const v = inertiaErrors[k];
        if (typeof v === 'string') {
            out[k] = v;
        } else if (Array.isArray(v) && v[0] !== undefined) {
            out[k] = String(v[0]);
        }
    }

    return out;
}

function fieldsFromPrefill(
    prefill: IndentCreatePrefill | null | undefined,
    stagedPdf: File | null | undefined,
): FormFields {
    const base = emptyFields();
    if (!prefill) {
        return { ...base, pdf: stagedPdf ?? null };
    }

    return {
        siding_id:
            prefill.siding_id !== undefined ? String(prefill.siding_id) : '',
        rake_number: prefill.rake_number ?? '',
        rake_priority_number:
            prefill.rake_priority_number !== undefined
                ? String(prefill.rake_priority_number)
                : '',
        expected_loading_date: prefill.expected_loading_date ?? '',
        demanded_stock: prefill.demanded_stock ?? '',
        total_units:
            prefill.total_units !== undefined ? String(prefill.total_units) : '',
        target_quantity_mt:
            prefill.target_quantity_mt !== undefined
                ? String(prefill.target_quantity_mt)
                : '',
        destination: prefill.destination ?? '',
        indent_at: prefill.indent_at ?? '',
        indent_number: prefill.indent_number ?? '',
        e_demand_reference_id: prefill.e_demand_reference_id ?? '',
        fnr_number: prefill.fnr_number ?? '',
        remarks: prefill.remarks ?? '',
        pdf: stagedPdf ?? null,
    };
}

const selectClassName = cn(
    'border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none',
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
);

interface IndentCreateFormFieldsProps {
    form: InertiaFormProps<FormFields>;
    sidings: SidingOption[];
    power_plants: PowerPlantOption[];
    variant: 'page' | 'modal';
    onCancel?: () => void;
    /** Extra busy state (e.g. fetch submit in PDF modal). */
    isSubmitting?: boolean;
    /** Server-side messages (e.g. JSON 422 from fetch) merged over Inertia `form.errors`. */
    fieldErrorOverrides?: Partial<Record<keyof FormFields, string>>;
    /** Extra validation copy (unknown attributes, or top-level message only). */
    serverErrorBanner?: string | null;
}

function IndentCreateFormFields({
    form,
    sidings,
    power_plants,
    variant,
    onCancel,
    isSubmitting = false,
    fieldErrorOverrides,
    serverErrorBanner,
}: IndentCreateFormFieldsProps) {
    const errors = useMemo(
        () => mergeIndentFieldMessages(form.errors, fieldErrorOverrides),
        [form.errors, fieldErrorOverrides],
    );

    return (
        <>
            {serverErrorBanner ? (
                <div
                    id="indent-create-form-server-errors"
                    role="alert"
                    className="bg-destructive/10 text-destructive mb-4 rounded-md border border-destructive/30 px-3 py-2 text-sm whitespace-pre-wrap"
                >
                    {serverErrorBanner}
                </div>
            ) : null}
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Rake details</CardTitle>
                    <CardDescription>
                        Siding, rake identifiers, stock and quantities, destination, and
                        e-demand date. All fields in this section are required. A linked rake
                        is created right after saving.
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div className="grid gap-2 md:col-span-3">
                        <Label htmlFor="siding_id">Siding *</Label>
                        <select
                            id="siding_id"
                            required
                            value={form.data.siding_id}
                            onChange={(e) =>
                                form.setData('siding_id', e.target.value)
                            }
                            className={selectClassName}
                        >
                            <option value="" disabled>
                                Select siding
                            </option>
                            {sidings.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name} ({s.code})
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.siding_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="rake_number">Rake sequence *</Label>
                        <Input
                            id="rake_number"
                            required
                            placeholder="e.g. Rake Sq. Number"
                            value={form.data.rake_number}
                            onChange={(e) =>
                                form.setData('rake_number', e.target.value)
                            }
                        />
                        <InputError message={errors.rake_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="rake_priority_number">
                            Priority number *
                        </Label>
                        <Input
                            id="rake_priority_number"
                            type="number"
                            required
                            min={0}
                            step={1}
                            placeholder="Priority"
                            value={form.data.rake_priority_number}
                            onChange={(e) =>
                                form.setData(
                                    'rake_priority_number',
                                    e.target.value,
                                )
                            }
                        />
                        <InputError message={errors.rake_priority_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="expected_loading_date">
                            Loading date (rake) *
                        </Label>
                        <Input
                            id="expected_loading_date"
                            type="date"
                            required
                            value={form.data.expected_loading_date}
                            onChange={(e) =>
                                form.setData(
                                    'expected_loading_date',
                                    e.target.value,
                                )
                            }
                        />
                        <InputError message={errors.expected_loading_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="demanded_stock">
                            Demanded stock (wagon type) *
                        </Label>
                        <Input
                            id="demanded_stock"
                            required
                            placeholder="e.g. BOBRN"
                            value={form.data.demanded_stock}
                            onChange={(e) =>
                                form.setData('demanded_stock', e.target.value)
                            }
                        />
                        <InputError message={errors.demanded_stock} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="total_units">Total units *</Label>
                        <Input
                            id="total_units"
                            type="number"
                            required
                            min={1}
                            step={1}
                            placeholder="Wagons"
                            value={form.data.total_units}
                            onChange={(e) =>
                                form.setData('total_units', e.target.value)
                            }
                        />
                        <InputError message={errors.total_units} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="target_quantity_mt">
                            Target quantity (MT) *
                        </Label>
                        <Input
                            id="target_quantity_mt"
                            type="number"
                            required
                            min={0}
                            step="0.01"
                            value={form.data.target_quantity_mt}
                            onChange={(e) =>
                                form.setData(
                                    'target_quantity_mt',
                                    e.target.value,
                                )
                            }
                        />
                        <InputError message={errors.target_quantity_mt} />
                    </div>
                    <div className="grid gap-2 md:col-span-2">
                        <Label htmlFor="destination">
                            Destination (power plant) *
                        </Label>
                        <select
                            id="destination"
                            required
                            value={form.data.destination}
                            onChange={(e) =>
                                form.setData('destination', e.target.value)
                            }
                            className={selectClassName}
                        >
                            <option value="" disabled>
                                Select power plant
                            </option>
                            {power_plants.map((p) => (
                                <option key={p.code} value={p.code}>
                                    {p.name} ({p.code})
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.destination} />
                    </div>
                    <div className="grid gap-2 md:col-span-1">
                        <Label htmlFor="indent_at">
                            E-Demand date &amp; time *
                        </Label>
                        <Input
                            id="indent_at"
                            type="datetime-local"
                            required
                            value={form.data.indent_at}
                            onChange={(e) =>
                                form.setData('indent_at', e.target.value)
                            }
                        />
                        <InputError message={errors.indent_at} />
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-base">
                        <FileText className="size-4" />
                        Location & status
                    </CardTitle>
                    <CardDescription>
                        Official e-demand / forwarding note number
                    </CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="indent_number">
                            E-Demand / forwarding note number
                        </Label>
                        <Input
                            id="indent_number"
                            placeholder="e.g. 302.001"
                            value={form.data.indent_number}
                            onChange={(e) =>
                                form.setData('indent_number', e.target.value)
                            }
                        />
                        <InputError message={errors.indent_number} />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Railway references</CardTitle>
                    <CardDescription>e-Demand reference ID and FNR</CardDescription>
                </CardHeader>
                <CardContent className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="e_demand_reference_id">
                            e-Demand reference ID
                        </Label>
                        <Input
                            id="e_demand_reference_id"
                            value={form.data.e_demand_reference_id}
                            onChange={(e) =>
                                form.setData(
                                    'e_demand_reference_id',
                                    e.target.value,
                                )
                            }
                        />
                        <InputError message={errors.e_demand_reference_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="fnr_number">FNR number</Label>
                        <Input
                            id="fnr_number"
                            value={form.data.fnr_number}
                            onChange={(e) =>
                                form.setData('fnr_number', e.target.value)
                            }
                        />
                        <InputError message={errors.fnr_number} />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">E-Demand PDF & notes</CardTitle>
                    <CardDescription>
                        Attach a confirmation PDF (optional)
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="pdf">e-Demand confirmation (PDF)</Label>
                        {form.data.pdf ? (
                            <p className="text-sm text-muted-foreground">
                                Selected: {form.data.pdf.name}
                            </p>
                        ) : null}
                        <Input
                            id="pdf"
                            type="file"
                            accept=".pdf,application/pdf"
                            onChange={(e) => {
                                const f = e.target.files?.[0];
                                form.setData('pdf', f ?? null);
                            }}
                        />
                        <InputError message={errors.pdf} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="remarks">Remarks</Label>
                        <textarea
                            id="remarks"
                            rows={4}
                            value={form.data.remarks}
                            onChange={(e) =>
                                form.setData('remarks', e.target.value)
                            }
                            className={cn(
                                'border-input bg-background placeholder:text-muted-foreground min-h-[100px] w-full rounded-md border px-3 py-2 text-sm shadow-xs',
                                'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] outline-none',
                            )}
                        />
                        <InputError message={errors.remarks} />
                    </div>
                </CardContent>
            </Card>

            <div className="flex flex-wrap gap-2">
                <Button
                    type="submit"
                    disabled={form.processing || isSubmitting}
                    data-pan={
                        variant === 'modal'
                            ? 'indents-create-modal-submit'
                            : 'indents-create-submit'
                    }
                >
                    {form.processing || isSubmitting ? 'Saving…' : 'Create e-demand'}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    data-pan={
                        variant === 'modal'
                            ? 'indents-create-modal-cancel'
                            : 'indents-create-cancel'
                    }
                    onClick={() => {
                        if (onCancel) {
                            onCancel();
                        } else {
                            router.visit('/indents');
                        }
                    }}
                >
                    Cancel
                </Button>
            </div>
        </>
    );
}

interface IndentCreateFormProps {
    sidings: SidingOption[];
    power_plants: PowerPlantOption[];
    prefill?: IndentCreatePrefill | null;
    stagedPdfFile?: File | null;
    variant: 'page' | 'modal';
    preserveStateOnSubmit?: boolean;
    /** Bumps when a new PDF is parsed; resets remembered form state for that import. */
    prefillSyncKey?: number;
    onCancel?: () => void;
    /** Called after a successful create (before redirect); clear any remembered UI state. */
    onSubmitSuccess?: () => void;
}

function IndentCreateFormPdfImportModal({
    sidings,
    power_plants,
    prefill,
    stagedPdfFile,
    prefillSyncKey,
    onCancel,
    onSubmitSuccess,
}: Omit<
    IndentCreateFormProps,
    'variant' | 'preserveStateOnSubmit'
>): JSX.Element {
    const form = useForm<FormFields>(() =>
        fieldsFromPrefill(prefill, stagedPdfFile),
    );
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [fetchFieldErrors, setFetchFieldErrors] = useState<
        Partial<Record<keyof FormFields, string>>
    >({});
    const [formErrorBanner, setFormErrorBanner] = useState<string | null>(null);

    useEffect(() => {
        form.setData(fieldsFromPrefill(prefill, stagedPdfFile));
        setFetchFieldErrors({});
        setFormErrorBanner(null);
    }, [prefillSyncKey]);

    useLayoutEffect(() => {
        const hasFieldErr = INDENT_FORM_SCROLL_ORDER.some(
            (k) => Boolean(fetchFieldErrors[k]),
        );
        if (!hasFieldErr && !formErrorBanner) {
            return;
        }

        requestAnimationFrame(() => {
            for (const key of INDENT_FORM_SCROLL_ORDER) {
                if (fetchFieldErrors[key]) {
                    document
                        .getElementById(String(key))
                        ?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                        });

                    return;
                }
            }
            document
                .getElementById('indent-create-form-server-errors')
                ?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }, [fetchFieldErrors, formErrorBanner]);

    const submit = async (e: React.FormEvent) => {
        e.preventDefault();
        form.clearErrors();
        setFetchFieldErrors({});
        setFormErrorBanner(null);
        setIsSubmitting(true);
        try {
            const fd = new FormData();
            appendIndentStoreFormData(form.data, fd);
            const result = await postFormDataExpectJson<{ redirect: string }>(
                '/indents',
                fd,
            );
            if (!result.ok) {
                if (result.status === 422) {
                    const { fields, banner } = parseLaravel422Response(
                        result.body,
                    );
                    setFetchFieldErrors(fields);
                    setFormErrorBanner(banner);
                } else {
                    const msg =
                        typeof result.body === 'object' &&
                        result.body !== null &&
                        'message' in result.body
                            ? String(
                                  (result.body as { message: unknown }).message,
                              )
                            : 'Could not save the e-demand.';
                    setFetchFieldErrors({ rake_number: msg });
                }

                return;
            }

            onSubmitSuccess?.();
            router.visit(result.data.redirect);
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6" noValidate>
            <IndentCreateFormFields
                form={form}
                sidings={sidings}
                power_plants={power_plants}
                variant="modal"
                onCancel={onCancel}
                isSubmitting={isSubmitting}
                fieldErrorOverrides={fetchFieldErrors}
                serverErrorBanner={formErrorBanner}
            />
        </form>
    );
}

function IndentCreateFormDefault({
    sidings,
    power_plants,
    prefill,
    stagedPdfFile,
    variant,
    preserveStateOnSubmit = false,
    onCancel,
}: IndentCreateFormProps): JSX.Element {
    const form = useForm<FormFields>(fieldsFromPrefill(prefill, stagedPdfFile));

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/indents', {
            forceFormData: true,
            preserveState: preserveStateOnSubmit,
            preserveScroll: preserveStateOnSubmit,
        });
    };

    const formInner = (
        <form onSubmit={submit} className="space-y-6" noValidate>
            <IndentCreateFormFields
                form={form}
                sidings={sidings}
                power_plants={power_plants}
                variant={variant}
                onCancel={onCancel}
            />
        </form>
    );

    if (variant === 'modal') {
        return formInner;
    }

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                        <Plus className="size-6 text-muted-foreground" />
                        Create e-demand
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Enter e-demand details. A linked rake will be created automatically
                        after saving.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    data-pan="indents-create-back"
                    onClick={() => router.visit('/indents')}
                >
                    Back to e-demand
                </Button>
            </div>
            {formInner}
        </div>
    );
}

export function IndentCreateForm(props: IndentCreateFormProps) {
    if (props.variant === 'modal' && props.preserveStateOnSubmit) {
        return (
            <IndentCreateFormPdfImportModal
                sidings={props.sidings}
                power_plants={props.power_plants}
                prefill={props.prefill}
                stagedPdfFile={props.stagedPdfFile}
                prefillSyncKey={props.prefillSyncKey ?? 0}
                onCancel={props.onCancel}
                onSubmitSuccess={props.onSubmitSuccess}
            />
        );
    }

    return <IndentCreateFormDefault {...props} />;
}
