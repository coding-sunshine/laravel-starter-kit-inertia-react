import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

interface MediaItem {
    id: number;
    name: string;
    file_name: string;
    url: string;
}

interface SidingBrief {
    id: number;
    name: string;
    code: string;
}

interface Registration {
    id: number;
    siding_id: number | null;
    siding?: SidingBrief | null;
    work_order_no_1: string | null;
    work_order_no_2: string | null;
    reference_no: string | null;
    work_order_date: string | null;
    transporter_name: string | null;
    trade_name: string | null;
    legal_name_of_business: string | null;
    pan_card: string | null;
    gst_no: string | null;
    status: string | null;
    email: string | null;
    vendor_code: string | null;
    mobile_1: string | null;
    mobile_2: string | null;
    address: string | null;
    gramin_or_non_gramin: string | null;
    is_active?: boolean | null;
    media: {
        pan_documents: MediaItem[];
        gst_documents: MediaItem[];
        transporter_documents: MediaItem[];
    };
}

interface Permissions {
    canUpdate: boolean;
}

interface Props {
    registration: Registration;
    sidings: SidingBrief[];
    permissions: Permissions;
}

function dateInputValue(d: string | null): string {
    if (!d) return '';
    if (/^\d{4}-\d{2}-\d{2}/.test(d)) {
        return d.slice(0, 10);
    }
    try {
        return new Date(d).toISOString().slice(0, 10);
    } catch {
        return '';
    }
}

function graminOrNonGraminLabel(v: string | null | undefined): string {
    if (v === 'gramin') {
        return 'Gramin';
    }
    if (v === 'non_gramin') {
        return 'Non-Gramin';
    }
    return v ?? '—';
}

export default function TransportWorkOrderRegistrationsEdit({ registration, sidings, permissions }: Props) {
    const { errors, flash } = usePage<{
        errors?: Record<string, string>;
        flash?: { success?: string };
    }>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Transporters', href: '/vehicle-workorders?view=transporters' },
        {
            title: 'Edit registration',
            href: `/vehicle-workorders/transport-registrations/${registration.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!permissions.canUpdate) return;
        const form = e.currentTarget;
        const fd = new FormData(form);
        fd.append('_method', 'put');
        router.post(`/vehicle-workorders/transport-registrations/${registration.id}`, fd, {
            forceFormData: true,
        });
    };

    const deleteRegistrationMedia = (mediaId: number) => {
        if (!permissions.canUpdate) {
            return;
        }
        if (!window.confirm('Remove this file from the registration?')) {
            return;
        }
        router.delete(`/vehicle-workorders/transport-registrations/${registration.id}/media/${mediaId}`, {
            preserveScroll: true,
        });
    };

    const r = registration;
    const editable = permissions.canUpdate;
    const graminSelectValue =
        r.gramin_or_non_gramin === 'gramin' || r.gramin_or_non_gramin === 'non_gramin' ? r.gramin_or_non_gramin : '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit registration #${r.id}`} />

            <div className="space-y-6">
                <h1 className="text-2xl font-semibold tracking-tight">Edit registration #{r.id}</h1>
                {flash?.success ? (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                ) : null}
                <p className="text-sm text-muted-foreground">
                    Fields marked <span className="text-destructive">*</span> are required where shown. Work order numbers may only use
                    digits, spaces, <span className="font-mono text-xs">. / _ -</span>, and the letters <span className="font-medium">D</span>
                    , <span className="font-medium">P</span>, <span className="font-medium">K</span>. When a number encodes a siding, it
                    must match the siding you select.
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>Existing files</CardTitle>
                        <CardDescription>
                            Open a file to view it. The trash control on the left removes an attachment (requires update permission).
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-6 md:grid-cols-3">
                        <div>
                            <h3 className="mb-2 text-sm font-medium">PAN</h3>
                            <ul className="space-y-1.5 text-sm">
                                {r.media.pan_documents.map((m) => (
                                    <li key={m.id} className="flex items-center gap-1">
                                        {editable ? (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                className="text-destructive hover:bg-destructive/10 hover:text-destructive shrink-0"
                                                aria-label={`Remove ${m.file_name}`}
                                                title="Remove file"
                                                data-pan="vehicle-workorders-transport-registrations-media-delete"
                                                onClick={() => deleteRegistrationMedia(m.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        ) : null}
                                        <a
                                            href={m.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-primary min-w-0 flex-1 truncate underline"
                                            title={m.file_name}
                                        >
                                            {m.file_name}
                                        </a>
                                    </li>
                                ))}
                                {r.media.pan_documents.length === 0 && <li className="text-muted-foreground">None</li>}
                            </ul>
                        </div>
                        <div>
                            <h3 className="mb-2 text-sm font-medium">GST</h3>
                            <ul className="space-y-1.5 text-sm">
                                {r.media.gst_documents.map((m) => (
                                    <li key={m.id} className="flex items-center gap-1">
                                        {editable ? (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                className="text-destructive hover:bg-destructive/10 hover:text-destructive shrink-0"
                                                aria-label={`Remove ${m.file_name}`}
                                                title="Remove file"
                                                data-pan="vehicle-workorders-transport-registrations-media-delete"
                                                onClick={() => deleteRegistrationMedia(m.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        ) : null}
                                        <a
                                            href={m.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-primary min-w-0 flex-1 truncate underline"
                                            title={m.file_name}
                                        >
                                            {m.file_name}
                                        </a>
                                    </li>
                                ))}
                                {r.media.gst_documents.length === 0 && <li className="text-muted-foreground">None</li>}
                            </ul>
                        </div>
                        <div>
                            <h3 className="mb-2 text-sm font-medium">Other</h3>
                            <ul className="space-y-1.5 text-sm">
                                {r.media.transporter_documents.map((m) => (
                                    <li key={m.id} className="flex items-center gap-1">
                                        {editable ? (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                className="text-destructive hover:bg-destructive/10 hover:text-destructive shrink-0"
                                                aria-label={`Remove ${m.file_name}`}
                                                title="Remove file"
                                                data-pan="vehicle-workorders-transport-registrations-media-delete"
                                                onClick={() => deleteRegistrationMedia(m.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        ) : null}
                                        <a
                                            href={m.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-primary min-w-0 flex-1 truncate underline"
                                            title={m.file_name}
                                        >
                                            {m.file_name}
                                        </a>
                                    </li>
                                ))}
                                {r.media.transporter_documents.length === 0 && (
                                    <li className="text-muted-foreground">None</li>
                                )}
                            </ul>
                        </div>
                    </CardContent>
                </Card>

                <form onSubmit={handleSubmit} encType="multipart/form-data" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Work order</CardTitle>
                            <CardDescription>Siding and work order numbers (D / P / K only for letters).</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="siding_id">
                                    Siding <span className="text-destructive">*</span>
                                </Label>
                                {permissions.canUpdate ? (
                                    <select
                                        id="siding_id"
                                        name="siding_id"
                                        required
                                        defaultValue={r.siding_id != null ? String(r.siding_id) : ''}
                                        className={cn(
                                            'border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow]',
                                            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                        )}
                                    >
                                        <option value="" disabled>
                                            Select siding…
                                        </option>
                                        {sidings.map((s) => (
                                            <option key={s.id} value={String(s.id)}>
                                                {s.name} ({s.code})
                                            </option>
                                        ))}
                                    </select>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        {r.siding ? `${r.siding.name} (${r.siding.code})` : r.siding_id != null ? `#${r.siding_id}` : '—'}
                                    </p>
                                )}
                                <InputError message={errors?.siding_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="work_order_no_1">Work order no. 1</Label>
                                <Input
                                    id="work_order_no_1"
                                    name="work_order_no_1"
                                    defaultValue={r.work_order_no_1 ?? ''}
                                    disabled={!editable}
                                />
                                <InputError message={errors?.work_order_no_1} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="work_order_no_2">Work order no. 2</Label>
                                <Input
                                    id="work_order_no_2"
                                    name="work_order_no_2"
                                    defaultValue={r.work_order_no_2 ?? ''}
                                    disabled={!editable}
                                />
                                <InputError message={errors?.work_order_no_2} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="reference_no">Ref. no.</Label>
                                <Input id="reference_no" name="reference_no" defaultValue={r.reference_no ?? ''} disabled={!editable} />
                                <InputError message={errors?.reference_no} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="work_order_date">Work order date</Label>
                                <Input
                                    id="work_order_date"
                                    name="work_order_date"
                                    type="date"
                                    defaultValue={dateInputValue(r.work_order_date)}
                                    disabled={!editable}
                                />
                                <InputError message={errors?.work_order_date} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Transporter</CardTitle>
                            <CardDescription>
                                {!editable ? (
                                    <>
                                        Gramin / Non-Gramin:{' '}
                                        <span className="font-medium text-foreground">{graminOrNonGraminLabel(r.gramin_or_non_gramin)}</span>.
                                        This record is{' '}
                                        <span className="font-medium text-foreground">{r.is_active === false ? 'inactive' : 'active'}</span>.
                                    </>
                                ) : (
                                    <>
                                        Transporter name, email, legal name, address, and Gramin / Non-Gramin are required. Uncheck{' '}
                                        <span className="font-medium text-foreground">Active transporter</span> to mark inactive.
                                    </>
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="transporter_name">
                                    Transporter name <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="transporter_name"
                                    name="transporter_name"
                                    defaultValue={r.transporter_name ?? ''}
                                    required={editable}
                                    disabled={!editable}
                                />
                                <InputError message={errors?.transporter_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="trade_name">Trade name</Label>
                                <Input id="trade_name" name="trade_name" defaultValue={r.trade_name ?? ''} disabled={!editable} />
                                <InputError message={errors?.trade_name} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="legal_name_of_business">
                                    Legal name of business <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="legal_name_of_business"
                                    name="legal_name_of_business"
                                    defaultValue={r.legal_name_of_business ?? ''}
                                    required={editable}
                                    disabled={!editable}
                                />
                                <InputError message={errors?.legal_name_of_business} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="pan_card">PAN</Label>
                                <Input id="pan_card" name="pan_card" defaultValue={r.pan_card ?? ''} disabled={!editable} />
                                <InputError message={errors?.pan_card} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="gst_no">GST no.</Label>
                                <Input id="gst_no" name="gst_no" defaultValue={r.gst_no ?? ''} disabled={!editable} />
                                <InputError message={errors?.gst_no} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status (optional)</Label>
                                <Input id="status" name="status" defaultValue={r.status ?? ''} disabled={!editable} />
                                <InputError message={errors?.status} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="vendor_code">Vendor code</Label>
                                <Input id="vendor_code" name="vendor_code" defaultValue={r.vendor_code ?? ''} disabled={!editable} />
                                <InputError message={errors?.vendor_code} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="email">
                                    Email <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={r.email ?? ''}
                                    required={editable}
                                    disabled={!editable}
                                    autoComplete="email"
                                />
                                <InputError message={errors?.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="mobile_1">Mobile 1</Label>
                                <Input id="mobile_1" name="mobile_1" defaultValue={r.mobile_1 ?? ''} disabled={!editable} />
                                <InputError message={errors?.mobile_1} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="mobile_2">Mobile 2</Label>
                                <Input id="mobile_2" name="mobile_2" defaultValue={r.mobile_2 ?? ''} disabled={!editable} />
                                <InputError message={errors?.mobile_2} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="address">
                                    Address <span className="text-destructive">*</span>
                                </Label>
                                <Textarea id="address" name="address" rows={3} defaultValue={r.address ?? ''} required={editable} disabled={!editable} />
                                <InputError message={errors?.address} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="gramin_or_non_gramin">
                                    Gramin / Non-Gramin <span className="text-destructive">*</span>
                                </Label>
                                <select
                                    id="gramin_or_non_gramin"
                                    name="gramin_or_non_gramin"
                                    required={editable}
                                    disabled={!editable}
                                    defaultValue={graminSelectValue}
                                    className={cn(
                                        'border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow]',
                                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                        !editable && 'cursor-not-allowed opacity-70',
                                    )}
                                >
                                    <option value="" disabled>
                                        Select…
                                    </option>
                                    <option value="gramin">Gramin</option>
                                    <option value="non_gramin">Non-Gramin</option>
                                </select>
                                <InputError message={errors?.gramin_or_non_gramin} />
                            </div>
                            <div className="flex items-center gap-2 md:col-span-2">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={r.is_active !== false}
                                    disabled={!editable}
                                    className="border-input text-primary focus-visible:ring-ring/50 size-4 rounded border shadow-xs focus-visible:ring-[3px] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-70"
                                />
                                <Label htmlFor="is_active" className={cn('cursor-pointer font-normal', !editable && 'cursor-default')}>
                                    Active transporter
                                </Label>
                                <InputError message={errors?.is_active} />
                            </div>
                        </CardContent>
                    </Card>

                    {permissions.canUpdate && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Add documents</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="pan_documents">PAN documents</Label>
                                    <Input id="pan_documents" name="pan_documents[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" />
                                    <InputError message={errors?.pan_documents ?? errors?.['pan_documents.0']} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="gst_documents">GST documents</Label>
                                    <Input id="gst_documents" name="gst_documents[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" />
                                    <InputError message={errors?.gst_documents ?? errors?.['gst_documents.0']} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="transporter_documents">Other transporter files</Label>
                                    <Input
                                        id="transporter_documents"
                                        name="transporter_documents[]"
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                    />
                                    <InputError message={errors?.transporter_documents ?? errors?.['transporter_documents.0']} />
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex gap-3">
                        {permissions.canUpdate ? (
                            <Button type="submit" data-pan="vehicle-workorders-transport-registrations-edit-submit">
                                Save changes
                            </Button>
                        ) : (
                            <p className="text-sm text-muted-foreground">You do not have permission to update this record.</p>
                        )}
                        <Button type="button" variant="outline" asChild>
                            <Link href="/vehicle-workorders?view=transporters">Back to transporters</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
