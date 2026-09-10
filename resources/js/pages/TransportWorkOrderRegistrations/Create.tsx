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

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Props {
    sidings: Siding[];
}

export default function TransportWorkOrderRegistrationsCreate({ sidings }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Transporters', href: '/vehicle-workorders?view=transporters' },
        { title: 'New transporter registration', href: '/vehicle-workorders/transport-registrations/create' },
    ];

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        router.post('/vehicle-workorders/transport-registrations', new FormData(form), {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create transport WO registration" />

            <div className="space-y-6">
                <div className="flex flex-col gap-2">
                    <h1 className="text-2xl font-semibold tracking-tight">Create registration</h1>
                    <p className="text-sm text-muted-foreground">
                        Choose the siding, then fill transporter details. Fields marked <span className="text-destructive">*</span> are
                        required. Work order numbers only allow the letters <span className="font-medium">D</span>,{' '}
                        <span className="font-medium">P</span>, and <span className="font-medium">K</span> (for Dumka, Pakur, Kurwa), plus
                        digits, spaces, and <span className="font-mono text-xs">. / _ -</span>. If a number encodes a siding (e.g.{' '}
                        <code className="rounded bg-muted px-1">D1</code> or ends with <code className="rounded bg-muted px-1">WO-P123</code>
                        ), it must match the siding you select.
                    </p>
                </div>

                <form onSubmit={handleSubmit} encType="multipart/form-data" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Work order</CardTitle>
                            <CardDescription>Select siding first. Work order no. fields only allow digits, separators, and D / P / K.</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="siding_id">
                                    Siding <span className="text-destructive">*</span>
                                </Label>
                                <select
                                    id="siding_id"
                                    name="siding_id"
                                    required
                                    defaultValue=""
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
                                <InputError message={errors?.siding_id} />
                            </div>
                            <div className="grid gap-2 md:col-span-1">
                                <Label htmlFor="work_order_no_1">Work order no. 1</Label>
                                <Input id="work_order_no_1" name="work_order_no_1" />
                                <InputError message={errors?.work_order_no_1} />
                            </div>
                            <div className="grid gap-2 md:col-span-1">
                                <Label htmlFor="work_order_no_2">Work order no. 2</Label>
                                <Input id="work_order_no_2" name="work_order_no_2" />
                                <InputError message={errors?.work_order_no_2} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="reference_no">Ref. no.</Label>
                                <Input id="reference_no" name="reference_no" />
                                <InputError message={errors?.reference_no} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="work_order_date">Work order date</Label>
                                <Input id="work_order_date" name="work_order_date" type="date" />
                                <InputError message={errors?.work_order_date} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Transporter</CardTitle>
                            <CardDescription>
                                Transporter name, email, legal name, address, and Gramin / Non-Gramin are required. Uncheck{' '}
                                <span className="font-medium">Active transporter</span> to mark inactive.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="transporter_name">
                                    Transporter name <span className="text-destructive">*</span>
                                </Label>
                                <Input id="transporter_name" name="transporter_name" required />
                                <InputError message={errors?.transporter_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="trade_name">Trade name</Label>
                                <Input id="trade_name" name="trade_name" />
                                <InputError message={errors?.trade_name} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="legal_name_of_business">
                                    Legal name of business <span className="text-destructive">*</span>
                                </Label>
                                <Input id="legal_name_of_business" name="legal_name_of_business" required />
                                <InputError message={errors?.legal_name_of_business} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="pan_card">PAN</Label>
                                <Input id="pan_card" name="pan_card" />
                                <InputError message={errors?.pan_card} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="gst_no">GST no.</Label>
                                <Input id="gst_no" name="gst_no" />
                                <InputError message={errors?.gst_no} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status (optional)</Label>
                                <Input id="status" name="status" />
                                <InputError message={errors?.status} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="vendor_code">Vendor code</Label>
                                <Input id="vendor_code" name="vendor_code" />
                                <InputError message={errors?.vendor_code} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="email">
                                    Email <span className="text-destructive">*</span>
                                </Label>
                                <Input id="email" name="email" type="email" required autoComplete="email" />
                                <InputError message={errors?.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="mobile_1">Mobile 1</Label>
                                <Input id="mobile_1" name="mobile_1" />
                                <InputError message={errors?.mobile_1} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="mobile_2">Mobile 2</Label>
                                <Input id="mobile_2" name="mobile_2" />
                                <InputError message={errors?.mobile_2} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="address">
                                    Address <span className="text-destructive">*</span>
                                </Label>
                                <Textarea id="address" name="address" rows={3} required />
                                <InputError message={errors?.address} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="gramin_or_non_gramin">
                                    Gramin / Non-Gramin <span className="text-destructive">*</span>
                                </Label>
                                <select
                                    id="gramin_or_non_gramin"
                                    name="gramin_or_non_gramin"
                                    required
                                    defaultValue=""
                                    className={cn(
                                        'border-input bg-background flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow]',
                                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
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
                                    defaultChecked
                                    className="border-input text-primary focus-visible:ring-ring/50 size-4 rounded border shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                />
                                <Label htmlFor="is_active" className="cursor-pointer font-normal">
                                    Active transporter
                                </Label>
                                <InputError message={errors?.is_active} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Documents</CardTitle>
                            <CardDescription>You can attach multiple files per category (PDF or images).</CardDescription>
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

                    <div className="flex gap-3">
                        <Button type="submit" data-pan="vehicle-workorders-transport-registrations-create-submit">
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/vehicle-workorders?view=transporters">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
