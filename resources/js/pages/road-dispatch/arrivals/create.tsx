import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, usePage } from '@inertiajs/react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Vehicle {
    id: number;
    vehicle_number: string;
    owner_name: string | null;
}

interface Props {
    sidings: Siding[];
    vehicles: Vehicle[];
}

export default function RoadDispatchArrivalsCreate({
    sidings,
    vehicles,
}: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Road Dispatch', href: '/road-dispatch/arrivals' },
        { title: 'Vehicle Arrivals', href: '/road-dispatch/arrivals' },
        { title: 'Record arrival', href: '/road-dispatch/arrivals/create' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Record vehicle arrival" />
            <div className="space-y-6">
                <h2 className="text-lg font-medium">Record vehicle arrival</h2>
                <Form
                    method="post"
                    action="/road-dispatch/arrivals"
                    className="max-w-md space-y-4"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="siding_id">Siding *</Label>
                        <select
                            id="siding_id"
                            name="siding_id"
                            required
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            <option value="">Select siding</option>
                            {sidings.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name} ({s.code})
                                </option>
                            ))}
                        </select>
                        <InputError message={errors?.siding_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="vehicle_id">Vehicle *</Label>
                        <select
                            id="vehicle_id"
                            name="vehicle_id"
                            required
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            <option value="">Select vehicle</option>
                            {vehicles.map((v) => (
                                <option key={v.id} value={v.id}>
                                    {v.vehicle_number}{' '}
                                    {v.owner_name ? `(${v.owner_name})` : ''}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors?.vehicle_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="arrived_at">Arrived at</Label>
                        <Input
                            id="arrived_at"
                            name="arrived_at"
                            type="datetime-local"
                            className="text-sm"
                        />
                        <InputError message={errors?.arrived_at} />
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
                        <Label htmlFor="gross_weight">Gross weight (MT)</Label>
                        <Input
                            id="gross_weight"
                            name="gross_weight"
                            type="number"
                            step="0.01"
                            min="0"
                            className="text-sm"
                        />
                        <InputError message={errors?.gross_weight} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="tare_weight">Tare weight (MT)</Label>
                        <Input
                            id="tare_weight"
                            name="tare_weight"
                            type="number"
                            step="0.01"
                            min="0"
                            className="text-sm"
                        />
                        <InputError message={errors?.tare_weight} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notes</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows={2}
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        />
                        <InputError message={errors?.notes} />
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
            </div>
        </AppLayout>
    );
}
