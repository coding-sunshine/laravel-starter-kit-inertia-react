import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TransporterRegistrationCombobox } from '@/components/vehicle-workorders/transporter-registration-combobox';
import { type BreadcrumbItem } from '@/types';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Props {
    sidings: Siding[];
}

const DOCUMENT_ACCEPT = '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx';

export default function VehicleWorkordersCreate({ sidings }: Props) {
    const defaultSidingId = sidings[0]?.id?.toString() ?? '';

    const { data, setData, post, processing, errors } = useForm({
        siding_id: defaultSidingId,
        vehicle_no: '',
        rcd_pin_no: '',
        transport_name: '',
        wo_no: '',
        wo_no_2: '',
        work_order_date: '',
        issued_date: '',
        proprietor_name: '',
        represented_by: '',
        place: '',
        address: '',
        tyres: '',
        tare_weight: '',
        mobile_no_1: '',
        mobile_no_2: '',
        owner_type: '',
        regd_date: '',
        permit_validity_date: '',
        tax_validity_date: '',
        fitness_validity_date: '',
        insurance_validity_date: '',
        maker_model: '',
        make: '',
        model: '',
        remarks: '',
        recommended_by: '',
        referenced: '',
        local_or_non_local: '',
        pan_no: '',
        gst_no: '',
        vehicle_rc_certificate: null as File | null,
        vehicle_insurance_certificate: null as File | null,
        vehicle_other_documents: [] as File[],
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Vehicle Work Orders', href: '/vehicle-workorders' },
        { title: 'Create', href: '/vehicle-workorders/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/vehicle-workorders', { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Vehicle Work Order" />

            <div className="space-y-6">
                <Heading
                    title="Create Vehicle Work Order"
                    description="Add a new vehicle work order record"
                />

                <form onSubmit={handleSubmit} encType="multipart/form-data">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Transporter &amp; work order</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <TransporterRegistrationCombobox
                                    includeSidingInDefaults
                                    initialSelection={null}
                                    defaultSidingIdForClear={defaultSidingId}
                                    setData={(field, value) =>
                                        setData(field as keyof typeof data, value)
                                    }
                                />

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <Label htmlFor="siding_id">
                                            Siding <span className="text-destructive">*</span>
                                        </Label>
                                        <Select
                                            value={data.siding_id}
                                            onValueChange={(v) => setData('siding_id', v)}
                                        >
                                            <SelectTrigger id="siding_id">
                                                <SelectValue placeholder="Select siding" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {sidings.map((s) => (
                                                    <SelectItem key={s.id} value={s.id.toString()}>
                                                        {s.name} ({s.code})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.siding_id && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.siding_id}
                                            </p>
                                        )}
                                    </div>

                                    <div>
                                        <Label htmlFor="work_order_date">
                                            Work order date{' '}
                                            <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="work_order_date"
                                            type="date"
                                            value={data.work_order_date}
                                            onChange={(e) =>
                                                setData('work_order_date', e.target.value)
                                            }
                                            required
                                        />
                                        {errors.work_order_date && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.work_order_date}
                                            </p>
                                        )}
                                    </div>

                                    <div className="md:col-span-2">
                                        <Label htmlFor="transport_name">
                                            Transporter name{' '}
                                            <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="transport_name"
                                            value={data.transport_name}
                                            onChange={(e) =>
                                                setData('transport_name', e.target.value)
                                            }
                                            required
                                        />
                                        {errors.transport_name && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.transport_name}
                                            </p>
                                        )}
                                    </div>

                                    <div className="md:col-span-2">
                                        <Label htmlFor="wo_no_2">
                                            WO no. 2 <span className="text-destructive">*</span>
                                        </Label>
                                        <Input
                                            id="wo_no_2"
                                            value={data.wo_no_2}
                                            onChange={(e) => setData('wo_no_2', e.target.value)}
                                            required
                                        />
                                        {errors.wo_no_2 && (
                                            <p className="mt-1 text-sm text-destructive">
                                                {errors.wo_no_2}
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="vehicle_no">
                                        Vehicle no. <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="vehicle_no"
                                        value={data.vehicle_no}
                                        onChange={(e) => setData('vehicle_no', e.target.value)}
                                        required
                                    />
                                    {errors.vehicle_no && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.vehicle_no}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="rcd_pin_no">
                                        RCD PIN no. <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="rcd_pin_no"
                                        value={data.rcd_pin_no}
                                        onChange={(e) => setData('rcd_pin_no', e.target.value)}
                                        required
                                    />
                                    {errors.rcd_pin_no && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.rcd_pin_no}
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle details</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="tyres">
                                        Tyres <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="tyres"
                                        type="number"
                                        min={1}
                                        value={data.tyres}
                                        onChange={(e) => setData('tyres', e.target.value)}
                                        required
                                    />
                                    {errors.tyres && (
                                        <p className="mt-1 text-sm text-destructive">{errors.tyres}</p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="tare_weight">
                                        Tare weight <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="tare_weight"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={data.tare_weight}
                                        onChange={(e) =>
                                            setData('tare_weight', e.target.value)
                                        }
                                        required
                                    />
                                    {errors.tare_weight && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.tare_weight}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="maker_model">Maker model</Label>
                                    <Input
                                        id="maker_model"
                                        value={data.maker_model}
                                        onChange={(e) =>
                                            setData('maker_model', e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="make">Make</Label>
                                    <Input
                                        id="make"
                                        value={data.make}
                                        onChange={(e) => setData('make', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="model">Model</Label>
                                    <Input
                                        id="model"
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="local_or_non_local">Local / Non-local</Label>
                                    <Input
                                        id="local_or_non_local"
                                        value={data.local_or_non_local}
                                        onChange={(e) =>
                                            setData('local_or_non_local', e.target.value)
                                        }
                                    />
                                </div>

                                <div className="md:col-span-2">
                                    <Label htmlFor="remarks">Remarks</Label>
                                    <textarea
                                        id="remarks"
                                        value={data.remarks}
                                        onChange={(e) => setData('remarks', e.target.value)}
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                        rows={3}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="recommended_by">Recommended by</Label>
                                    <Input
                                        id="recommended_by"
                                        value={data.recommended_by}
                                        onChange={(e) =>
                                            setData('recommended_by', e.target.value)
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle permits &amp; validity</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <Label htmlFor="issued_date">Issued date</Label>
                                    <Input
                                        id="issued_date"
                                        type="date"
                                        value={data.issued_date}
                                        onChange={(e) => setData('issued_date', e.target.value)}
                                    />
                                    {errors.issued_date && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.issued_date}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <Label htmlFor="regd_date">Regd date</Label>
                                    <Input
                                        id="regd_date"
                                        type="date"
                                        value={data.regd_date}
                                        onChange={(e) => setData('regd_date', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="permit_validity_date">Permit validity</Label>
                                    <Input
                                        id="permit_validity_date"
                                        type="date"
                                        value={data.permit_validity_date}
                                        onChange={(e) =>
                                            setData('permit_validity_date', e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="tax_validity_date">Tax validity</Label>
                                    <Input
                                        id="tax_validity_date"
                                        type="date"
                                        value={data.tax_validity_date}
                                        onChange={(e) =>
                                            setData('tax_validity_date', e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="fitness_validity_date">Fitness validity</Label>
                                    <Input
                                        id="fitness_validity_date"
                                        type="date"
                                        value={data.fitness_validity_date}
                                        onChange={(e) =>
                                            setData('fitness_validity_date', e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="insurance_validity_date">
                                        Insurance validity
                                    </Label>
                                    <Input
                                        id="insurance_validity_date"
                                        type="date"
                                        value={data.insurance_validity_date}
                                        onChange={(e) =>
                                            setData('insurance_validity_date', e.target.value)
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Representative &amp; location</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="represented_by">Represented by</Label>
                                    <Input
                                        id="represented_by"
                                        value={data.represented_by}
                                        onChange={(e) =>
                                            setData('represented_by', e.target.value)
                                        }
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="place">Place</Label>
                                    <Input
                                        id="place"
                                        value={data.place}
                                        onChange={(e) => setData('place', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="owner_type">Owner type</Label>
                                    <Input
                                        id="owner_type"
                                        value={data.owner_type}
                                        onChange={(e) => setData('owner_type', e.target.value)}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle documents</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="vehicle_rc_certificate">Vehicle RC</Label>
                                    <Input
                                        id="vehicle_rc_certificate"
                                        type="file"
                                        accept={DOCUMENT_ACCEPT}
                                        className="cursor-pointer"
                                        onChange={(e) => {
                                            const f = e.target.files?.[0];
                                            setData('vehicle_rc_certificate', f ?? null);
                                        }}
                                    />
                                    {errors.vehicle_rc_certificate && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.vehicle_rc_certificate}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="vehicle_insurance_certificate">
                                        Insurance
                                    </Label>
                                    <Input
                                        id="vehicle_insurance_certificate"
                                        type="file"
                                        accept={DOCUMENT_ACCEPT}
                                        className="cursor-pointer"
                                        onChange={(e) => {
                                            const f = e.target.files?.[0];
                                            setData('vehicle_insurance_certificate', f ?? null);
                                        }}
                                    />
                                    {errors.vehicle_insurance_certificate && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.vehicle_insurance_certificate}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="vehicle_other_documents">Other documents</Label>
                                    <Input
                                        id="vehicle_other_documents"
                                        type="file"
                                        accept={DOCUMENT_ACCEPT}
                                        multiple
                                        className="cursor-pointer"
                                        onChange={(e) => {
                                            const list = e.target.files;
                                            setData(
                                                'vehicle_other_documents',
                                                list?.length ? Array.from(list) : [],
                                            );
                                        }}
                                    />
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        PDF or images, up to 20 MB each. Multiple files allowed.
                                    </p>
                                    {errors['vehicle_other_documents.0'] && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors['vehicle_other_documents.0']}
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex justify-end gap-4">
                            <Link href="/vehicle-workorders">
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </Link>
                            <Button type="submit" disabled={processing}>
                                Create work order
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
