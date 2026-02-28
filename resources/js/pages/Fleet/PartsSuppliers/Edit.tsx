import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface PartsSupplier {
    id: number;
    name: string;
    code?: string;
    contact_name?: string;
    contact_phone?: string;
    contact_email?: string;
    address?: string;
    postcode?: string;
    city?: string;
    payment_terms?: string;
    minimum_order_value?: string;
    preferred?: boolean;
    is_active?: boolean;
}
interface Props { partsSupplier: PartsSupplier; }

export default function FleetPartsSuppliersEdit({ partsSupplier }: Props) {
    const form = useForm({
        name: partsSupplier.name,
        code: partsSupplier.code ?? '',
        contact_name: partsSupplier.contact_name ?? '',
        contact_phone: partsSupplier.contact_phone ?? '',
        contact_email: partsSupplier.contact_email ?? '',
        address: partsSupplier.address ?? '',
        postcode: partsSupplier.postcode ?? '',
        city: partsSupplier.city ?? '',
        payment_terms: partsSupplier.payment_terms ?? '',
        minimum_order_value: partsSupplier.minimum_order_value ?? '',
        preferred: partsSupplier.preferred ?? false,
        is_active: partsSupplier.is_active ?? true,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parts suppliers', href: '/fleet/parts-suppliers' },
        { title: 'Edit', href: `/fleet/parts-suppliers/${partsSupplier.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit parts supplier" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/parts-suppliers/${partsSupplier.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit parts supplier</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/parts-suppliers/${partsSupplier.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Name</Label>
                            <Input value={form.data.name} onChange={e => form.setData('name', e.target.value)} required />
                        </div>
                        <div className="space-y-2">
                            <Label>Code</Label>
                            <Input value={form.data.code} onChange={e => form.setData('code', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Contact name</Label>
                        <Input value={form.data.contact_name} onChange={e => form.setData('contact_name', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Contact phone</Label>
                            <Input value={form.data.contact_phone} onChange={e => form.setData('contact_phone', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Contact email</Label>
                            <Input type="email" value={form.data.contact_email} onChange={e => form.setData('contact_email', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Address</Label>
                        <Input value={form.data.address} onChange={e => form.setData('address', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>City</Label>
                            <Input value={form.data.city} onChange={e => form.setData('city', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Postcode</Label>
                            <Input value={form.data.postcode} onChange={e => form.setData('postcode', e.target.value)} />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Payment terms</Label>
                            <Input value={form.data.payment_terms} onChange={e => form.setData('payment_terms', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Minimum order value</Label>
                            <Input type="number" step="any" min={0} value={form.data.minimum_order_value} onChange={e => form.setData('minimum_order_value', e.target.value)} />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input type="checkbox" id="preferred" checked={form.data.preferred} onChange={e => form.setData('preferred', e.target.checked)} />
                        <Label htmlFor="preferred">Preferred</Label>
                    </div>
                    <div className="flex items-center gap-2">
                        <input type="checkbox" id="is_active" checked={form.data.is_active} onChange={e => form.setData('is_active', e.target.checked)} />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/parts-suppliers/${partsSupplier.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
