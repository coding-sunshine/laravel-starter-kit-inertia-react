import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';
import { ArrowLeft, Truck, Weight } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Arrival {
    id: number;
    arrived_at: string;
    gross_weight: string | null;
    tare_weight: string | null;
    net_weight: string | null;
    shift: string | null;
    siding: {
        id: number;
        name: string;
        code: string;
    };
    vehicle: {
        id: number;
        vehicle_number: string;
        owner_name: string | null;
    };
}

interface Props {
    arrivals: Arrival[];
    sidings: Siding[];
}

export default function RoadDispatchUnloadsCreate({
    arrivals,
    sidings,
}: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Road Dispatch', href: '/road-dispatch/unloads' },
        { title: 'Vehicle Unloads', href: '/road-dispatch/unloads' },
        { title: 'Record unload', href: '/road-dispatch/unloads/create' },
    ];

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString();
    };

    const formatDateTimeLocal = (dateString: string) => {
        const date = new Date(dateString);
        return date.toISOString().slice(0, 16);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Record vehicle unload" />
            <div className="space-y-6">
                <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={() => window.history.back()}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back to Unloads
                    </Button>
                </div>

                <div>
                    <h2 className="text-lg font-medium">Record vehicle unload</h2>
                    <p className="text-muted-foreground">
                        Select an arrival to create an unload record. Only arrivals without existing unloads are shown.
                    </p>
                </div>

                {arrivals.length === 0 ? (
                    <Card>
                        <CardContent className="p-8 text-center">
                            <Truck className="mx-auto h-12 w-12 text-muted-foreground" />
                            <h3 className="mt-4 text-lg font-medium">No arrivals available</h3>
                            <p className="mt-2 text-muted-foreground">
                                All arrivals have been processed or there are no arrivals in your accessible sidings.
                            </p>
                            <Button className="mt-4" onClick={() => window.history.back()}>
                                Go Back
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Unload Details</CardTitle>
                            <CardDescription>
                                Select an arrival and fill in unload information
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Form
                                method="post"
                                action="/road-dispatch/unloads"
                                className="max-w-2xl space-y-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="arrival_id">Select Arrival *</Label>
                                    <select
                                        id="arrival_id"
                                        name="arrival_id"
                                        required
                                        className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                        onChange={(e) => {
                                            const selectedArrival = arrivals.find(a => a.id === parseInt(e.target.value));
                                            if (selectedArrival) {
                                                const vehicleInfoInput = document.getElementById('vehicle_info') as HTMLInputElement;
                                                const sidingInput = document.getElementById('siding_info') as HTMLInputElement;
                                                const arrivalTimeInput = document.getElementById('arrival_time') as HTMLInputElement;
                                                const shiftSelect = document.getElementById('shift') as HTMLSelectElement;
                                                const weighmentInput = document.getElementById('weighment_weight_mt') as HTMLInputElement;
                                                
                                                vehicleInfoInput.value = `${selectedArrival.vehicle.vehicle_number} (${selectedArrival.vehicle.owner_name || 'No owner'})`;
                                                sidingInput.value = `${selectedArrival.siding.name} (${selectedArrival.siding.code})`;
                                                arrivalTimeInput.value = formatDateTimeLocal(selectedArrival.arrived_at);
                                                shiftSelect.value = selectedArrival.shift || '';
                                                weighmentInput.value = selectedArrival.gross_weight || '';
                                            }
                                        }}
                                    >
                                        <option value="">Select an arrival</option>
                                        {arrivals.map((arrival) => (
                                            <option key={arrival.id} value={arrival.id}>
                                                {arrival.vehicle.vehicle_number} - {arrival.siding.code} - {formatDate(arrival.arrived_at)}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors?.arrival_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="vehicle_info">Vehicle</Label>
                                    <Input
                                        id="vehicle_info"
                                        name="vehicle_info"
                                        type="text"
                                        readOnly
                                        className="bg-muted"
                                        placeholder="Select an arrival above"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="siding_info">Siding</Label>
                                    <Input
                                        id="siding_info"
                                        name="siding_info"
                                        type="text"
                                        readOnly
                                        className="bg-muted"
                                        placeholder="Select an arrival above"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="arrival_time">Arrival time *</Label>
                                    <Input
                                        id="arrival_time"
                                        name="arrival_time"
                                        type="datetime-local"
                                        required
                                        className="text-sm"
                                    />
                                    <InputError message={errors?.arrival_time} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="shift">Shift</Label>
                                    <select
                                        id="shift"
                                        name="shift"
                                        className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                    >
                                        <option value="">Select shift (optional)</option>
                                        <option value="morning">Morning</option>
                                        <option value="evening">Evening</option>
                                        <option value="night">Night</option>
                                    </select>
                                    <InputError message={errors?.shift} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="jimms_challan_number">
                                        JIMMS challan number
                                    </Label>
                                    <Input
                                        id="jimms_challan_number"
                                        name="jimms_challan_number"
                                        type="text"
                                        maxLength={30}
                                        className="text-sm"
                                    />
                                    <InputError message={errors?.jimms_challan_number} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="mine_weight_mt">Mine weight (MT)</Label>
                                    <Input
                                        id="mine_weight_mt"
                                        name="mine_weight_mt"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        className="text-sm"
                                    />
                                    <InputError message={errors?.mine_weight_mt} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="weighment_weight_mt">
                                        Weighment weight (MT)
                                    </Label>
                                    <Input
                                        id="weighment_weight_mt"
                                        name="weighment_weight_mt"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        className="text-sm"
                                    />
                                    <InputError message={errors?.weighment_weight_mt} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="remarks">Remarks</Label>
                                    <textarea
                                        id="remarks"
                                        name="remarks"
                                        rows={2}
                                        className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                    />
                                    <InputError message={errors?.remarks} />
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit">Save</Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => window.history.back()}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </Form>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
