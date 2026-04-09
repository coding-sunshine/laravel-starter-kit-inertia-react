import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Heading from '@/components/heading';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface VehicleWorkorder {
    id: number;
    siding_id: number;
    vehicle_no: string | null;
    rcd_pin_no: string | null;
    transport_name: string | null;
    wo_no: string | null;
    wo_no_2: string | null;
    work_order_date: string | null;
    issued_date: string | null;
    proprietor_name: string | null;
    represented_by: string | null;
    place: string | null;
    address: string | null;
    tyres: number | null;
    tare_weight: number | string | null;
    mobile_no_1: string | null;
    mobile_no_2: string | null;
    owner_type: string | null;
    regd_date: string | null;
    permit_validity_date: string | null;
    tax_validity_date: string | null;
    fitness_validity_date: string | null;
    insurance_validity_date: string | null;
    maker_model: string | null;
    make: string | null;
    model: string | null;
    remarks: string | null;
    recommended_by: string | null;
    referenced: string | null;
    local_or_non_local: string | null;
    pan_no: string | null;
    gst_no: string | null;
    siding?: Siding;
}

interface Props {
    vehicleWorkorder: VehicleWorkorder;
}

function toDateInput(dateStr: string | null): string {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr);
        return d.toISOString().slice(0, 10);
    } catch {
        return '';
    }
}

export default function VehicleWorkordersEdit({ vehicleWorkorder }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        vehicle_no: vehicleWorkorder.vehicle_no ?? '',
        rcd_pin_no: vehicleWorkorder.rcd_pin_no ?? '',
        transport_name: vehicleWorkorder.transport_name ?? '',
        wo_no: vehicleWorkorder.wo_no ?? '',
        wo_no_2: vehicleWorkorder.wo_no_2 ?? '',
        work_order_date: toDateInput(vehicleWorkorder.work_order_date),
        issued_date: toDateInput(vehicleWorkorder.issued_date),
        proprietor_name: vehicleWorkorder.proprietor_name ?? '',
        represented_by: vehicleWorkorder.represented_by ?? '',
        place: vehicleWorkorder.place ?? '',
        address: vehicleWorkorder.address ?? '',
        tyres: vehicleWorkorder.tyres?.toString() ?? '',
        tare_weight: vehicleWorkorder.tare_weight?.toString() ?? '',
        mobile_no_1: vehicleWorkorder.mobile_no_1 ?? '',
        mobile_no_2: vehicleWorkorder.mobile_no_2 ?? '',
        owner_type: vehicleWorkorder.owner_type ?? '',
        regd_date: toDateInput(vehicleWorkorder.regd_date),
        permit_validity_date: toDateInput(vehicleWorkorder.permit_validity_date),
        tax_validity_date: toDateInput(vehicleWorkorder.tax_validity_date),
        fitness_validity_date: toDateInput(vehicleWorkorder.fitness_validity_date),
        insurance_validity_date: toDateInput(vehicleWorkorder.insurance_validity_date),
        maker_model: vehicleWorkorder.maker_model ?? '',
        make: vehicleWorkorder.make ?? '',
        model: vehicleWorkorder.model ?? '',
        remarks: vehicleWorkorder.remarks ?? '',
        recommended_by: vehicleWorkorder.recommended_by ?? '',
        referenced: vehicleWorkorder.referenced ?? '',
        local_or_non_local: vehicleWorkorder.local_or_non_local ?? '',
        pan_no: vehicleWorkorder.pan_no ?? '',
        gst_no: vehicleWorkorder.gst_no ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/vehicle-workorders/${vehicleWorkorder.id}`);
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Vehicle Work Orders', href: '/vehicle-workorders' },
        {
            title: `Edit ${vehicleWorkorder.vehicle_no ?? vehicleWorkorder.wo_no ?? vehicleWorkorder.id}`,
            href: `/vehicle-workorders/${vehicleWorkorder.id}/edit`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Work Order ${vehicleWorkorder.vehicle_no ?? vehicleWorkorder.id}`} />

            <div className="space-y-6">
                <Heading
                    title={`Edit Work Order ${vehicleWorkorder.vehicle_no ?? `#${vehicleWorkorder.id}`}`}
                    description={`Siding: ${vehicleWorkorder.siding?.name ?? '-'}`}
                />

                <form onSubmit={handleSubmit}>
                    <div className="space-y-6">
                        {/* Vehicle & WO Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle & Work Order</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="vehicle_no">Vehicle No</Label>
                                    <Input
                                        id="vehicle_no"
                                        value={data.vehicle_no}
                                        onChange={(e) => setData('vehicle_no', e.target.value)}
                                    />
                                    {errors.vehicle_no && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.vehicle_no}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="rcd_pin_no">RCD PIN No</Label>
                                    <Input
                                        id="rcd_pin_no"
                                        value={data.rcd_pin_no}
                                        onChange={(e) => setData('rcd_pin_no', e.target.value)}
                                    />
                                    {errors.rcd_pin_no && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.rcd_pin_no}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="transport_name">Transport Name</Label>
                                    <Input
                                        id="transport_name"
                                        value={data.transport_name}
                                        onChange={(e) =>
                                            setData('transport_name', e.target.value)
                                        }
                                    />
                                    {errors.transport_name && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.transport_name}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="wo_no">WO No</Label>
                                    <Input
                                        id="wo_no"
                                        value={data.wo_no}
                                        onChange={(e) => setData('wo_no', e.target.value)}
                                    />
                                    {errors.wo_no && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.wo_no}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="wo_no_2">WO No 2</Label>
                                    <Input
                                        id="wo_no_2"
                                        value={data.wo_no_2}
                                        onChange={(e) => setData('wo_no_2', e.target.value)}
                                    />
                                    {errors.wo_no_2 && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.wo_no_2}
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Dates */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Dates</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <Label htmlFor="work_order_date">Work Order Date</Label>
                                    <Input
                                        id="work_order_date"
                                        type="date"
                                        value={data.work_order_date}
                                        onChange={(e) =>
                                            setData('work_order_date', e.target.value)
                                        }
                                    />
                                    {errors.work_order_date && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.work_order_date}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="issued_date">Issued Date</Label>
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
                                    <Label htmlFor="regd_date">Regd Date</Label>
                                    <Input
                                        id="regd_date"
                                        type="date"
                                        value={data.regd_date}
                                        onChange={(e) => setData('regd_date', e.target.value)}
                                    />
                                    {errors.regd_date && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.regd_date}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="permit_validity_date">
                                        Permit Validity Date
                                    </Label>
                                    <Input
                                        id="permit_validity_date"
                                        type="date"
                                        value={data.permit_validity_date}
                                        onChange={(e) =>
                                            setData('permit_validity_date', e.target.value)
                                        }
                                    />
                                    {errors.permit_validity_date && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.permit_validity_date}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="tax_validity_date">Tax Validity Date</Label>
                                    <Input
                                        id="tax_validity_date"
                                        type="date"
                                        value={data.tax_validity_date}
                                        onChange={(e) =>
                                            setData('tax_validity_date', e.target.value)
                                        }
                                    />
                                    {errors.tax_validity_date && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.tax_validity_date}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="fitness_validity_date">
                                        Fitness Validity Date
                                    </Label>
                                    <Input
                                        id="fitness_validity_date"
                                        type="date"
                                        value={data.fitness_validity_date}
                                        onChange={(e) =>
                                            setData('fitness_validity_date', e.target.value)
                                        }
                                    />
                                    {errors.fitness_validity_date && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.fitness_validity_date}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="insurance_validity_date">
                                        Insurance Validity Date
                                    </Label>
                                    <Input
                                        id="insurance_validity_date"
                                        type="date"
                                        value={data.insurance_validity_date}
                                        onChange={(e) =>
                                            setData('insurance_validity_date', e.target.value)
                                        }
                                    />
                                    {errors.insurance_validity_date && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.insurance_validity_date}
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Owner / Place / Contact */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Owner, Place & Contact</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="proprietor_name">Proprietor Name</Label>
                                    <Input
                                        id="proprietor_name"
                                        value={data.proprietor_name}
                                        onChange={(e) =>
                                            setData('proprietor_name', e.target.value)
                                        }
                                    />
                                    {errors.proprietor_name && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.proprietor_name}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="represented_by">Represented By</Label>
                                    <Input
                                        id="represented_by"
                                        value={data.represented_by}
                                        onChange={(e) =>
                                            setData('represented_by', e.target.value)
                                        }
                                    />
                                    {errors.represented_by && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.represented_by}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="place">Place</Label>
                                    <Input
                                        id="place"
                                        value={data.place}
                                        onChange={(e) => setData('place', e.target.value)}
                                    />
                                    {errors.place && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.place}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="owner_type">Owner Type</Label>
                                    <Input
                                        id="owner_type"
                                        value={data.owner_type}
                                        onChange={(e) => setData('owner_type', e.target.value)}
                                    />
                                    {errors.owner_type && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.owner_type}
                                        </p>
                                    )}
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="address">Address</Label>
                                    <textarea
                                        id="address"
                                        value={data.address}
                                        onChange={(e) => setData('address', e.target.value)}
                                        rows={2}
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    {errors.address && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.address}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="mobile_no_1">Mobile No 1</Label>
                                    <Input
                                        id="mobile_no_1"
                                        value={data.mobile_no_1}
                                        onChange={(e) =>
                                            setData('mobile_no_1', e.target.value)
                                        }
                                    />
                                    {errors.mobile_no_1 && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.mobile_no_1}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="mobile_no_2">Mobile No 2</Label>
                                    <Input
                                        id="mobile_no_2"
                                        value={data.mobile_no_2}
                                        onChange={(e) =>
                                            setData('mobile_no_2', e.target.value)
                                        }
                                    />
                                    {errors.mobile_no_2 && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.mobile_no_2}
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Vehicle Details & Tax */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Vehicle Details & Tax</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <Label htmlFor="tyres">Tyres</Label>
                                    <Input
                                        id="tyres"
                                        type="number"
                                        min={0}
                                        value={data.tyres}
                                        onChange={(e) => setData('tyres', e.target.value)}
                                    />
                                    {errors.tyres && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.tyres}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="tare_weight">Tare Weight</Label>
                                    <Input
                                        id="tare_weight"
                                        type="number"
                                        step="0.01"
                                        min={0}
                                        value={data.tare_weight}
                                        onChange={(e) =>
                                            setData('tare_weight', e.target.value)
                                        }
                                    />
                                    {errors.tare_weight && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.tare_weight}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="maker_model">Maker Model</Label>
                                    <Input
                                        id="maker_model"
                                        value={data.maker_model}
                                        onChange={(e) =>
                                            setData('maker_model', e.target.value)
                                        }
                                    />
                                    {errors.maker_model && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.maker_model}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="make">Make</Label>
                                    <Input
                                        id="make"
                                        value={data.make}
                                        onChange={(e) => setData('make', e.target.value)}
                                    />
                                    {errors.make && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.make}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="model">Model</Label>
                                    <Input
                                        id="model"
                                        value={data.model}
                                        onChange={(e) => setData('model', e.target.value)}
                                    />
                                    {errors.model && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.model}
                                        </p>
                                    )}
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
                                    {errors.local_or_non_local && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.local_or_non_local}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="pan_no">PAN No</Label>
                                    <Input
                                        id="pan_no"
                                        value={data.pan_no}
                                        onChange={(e) => setData('pan_no', e.target.value)}
                                    />
                                    {errors.pan_no && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.pan_no}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="gst_no">GST No</Label>
                                    <Input
                                        id="gst_no"
                                        value={data.gst_no}
                                        onChange={(e) => setData('gst_no', e.target.value)}
                                    />
                                    {errors.gst_no && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.gst_no}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="recommended_by">Recommended By</Label>
                                    <Input
                                        id="recommended_by"
                                        value={data.recommended_by}
                                        onChange={(e) =>
                                            setData('recommended_by', e.target.value)
                                        }
                                    />
                                    {errors.recommended_by && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.recommended_by}
                                        </p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="referenced">Referenced</Label>
                                    <Input
                                        id="referenced"
                                        value={data.referenced}
                                        onChange={(e) => setData('referenced', e.target.value)}
                                    />
                                    {errors.referenced && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.referenced}
                                        </p>
                                    )}
                                </div>
                                <div className="md:col-span-2">
                                    <Label htmlFor="remarks">Remarks</Label>
                                    <textarea
                                        id="remarks"
                                        value={data.remarks}
                                        onChange={(e) => setData('remarks', e.target.value)}
                                        rows={3}
                                        className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                    {errors.remarks && (
                                        <p className="mt-1 text-sm text-destructive">
                                            {errors.remarks}
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
                            <Button type="submit" disabled={processing} data-pan="vehicle-workorder-update">
                                Update Work Order
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
