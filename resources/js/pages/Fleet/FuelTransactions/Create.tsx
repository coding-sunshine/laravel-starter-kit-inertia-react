import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option { value: string; name: string; }
interface Props {
    fuelTypes: Option[];
    vehicles: { id: number; registration: string }[];
    drivers: { id: number; first_name: string; last_name: string }[];
    fuelCards: { id: number; card_number: string }[];
    fuelStations: { id: number; name: string }[];
}

export default function FleetFuelTransactionsCreate({ fuelTypes, vehicles, drivers, fuelCards }: Props) {
    const form = useForm({
        vehicle_id: '' as number | '',
        fuel_card_id: '' as number | '',
        transaction_timestamp: new Date().toISOString().slice(0, 16),
        fuel_type: fuelTypes[0]?.value ?? '',
        price_per_litre: '',
        total_cost: '',
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/fuel-transactions' },
        { title: 'Fuel transactions', href: '/fleet/fuel-transactions' },
        { title: 'Create', href: '/fleet/fuel-transactions/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((d) => ({
            ...d,
            price_per_litre: d.price_per_litre === '' ? undefined : Number(d.price_per_litre),
            total_cost: d.total_cost === '' ? undefined : Number(d.total_cost),
        }));
        form.post('/fleet/fuel-transactions');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New fuel transaction" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New fuel transaction</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="vehicle_id">Vehicle *</Label>
                        <select id="vehicle_id" value={data.vehicle_id === '' ? '' : String(data.vehicle_id)} onChange={(e) => setData('vehicle_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                            <option value="">Select</option>
                            {vehicles.map((v) => <option key={v.id} value={v.id}>{v.registration}</option>)}
                        </select>
                        {errors.vehicle_id && <p className="mt-1 text-sm text-destructive">{errors.vehicle_id}</p>}
                    </div>
                    <div>
                        <Label htmlFor="fuel_card_id">Fuel card *</Label>
                        <select id="fuel_card_id" value={data.fuel_card_id === '' ? '' : String(data.fuel_card_id)} onChange={(e) => setData('fuel_card_id', e.target.value === '' ? '' : Number(e.target.value))} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" required>
                            <option value="">Select</option>
                            {fuelCards.map((c) => <option key={c.id} value={c.id}>{c.card_number}</option>)}
                        </select>
                        {errors.fuel_card_id && <p className="mt-1 text-sm text-destructive">{errors.fuel_card_id}</p>}
                    </div>
                    <div>
                        <Label htmlFor="transaction_timestamp">Transaction date & time *</Label>
                        <Input id="transaction_timestamp" type="datetime-local" value={data.transaction_timestamp} onChange={(e) => setData('transaction_timestamp', e.target.value)} className="mt-1" />
                        {errors.transaction_timestamp && <p className="mt-1 text-sm text-destructive">{errors.transaction_timestamp}</p>}
                    </div>
                    <div>
                        <Label htmlFor="fuel_type">Fuel type *</Label>
                        <select id="fuel_type" value={data.fuel_type} onChange={(e) => setData('fuel_type', e.target.value)} className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                            {fuelTypes.map((o) => <option key={o.value} value={o.value}>{o.name}</option>)}
                        </select>
                        {errors.fuel_type && <p className="mt-1 text-sm text-destructive">{errors.fuel_type}</p>}
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="price_per_litre">Price per litre *</Label>
                            <Input id="price_per_litre" type="number" step="0.01" min="0" value={data.price_per_litre} onChange={(e) => setData('price_per_litre', e.target.value)} className="mt-1" />
                            {errors.price_per_litre && <p className="mt-1 text-sm text-destructive">{errors.price_per_litre}</p>}
                        </div>
                        <div>
                            <Label htmlFor="total_cost">Total cost *</Label>
                            <Input id="total_cost" type="number" step="0.01" min="0" value={data.total_cost} onChange={(e) => setData('total_cost', e.target.value)} className="mt-1" />
                            {errors.total_cost && <p className="mt-1 text-sm text-destructive">{errors.total_cost}</p>}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>Create fuel transaction</Button>
                        <Button variant="outline" asChild><Link href="/fleet/fuel-transactions">Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
