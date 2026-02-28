import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface PartsInventoryItem {
    id: number;
    garage_id?: number;
    part_number: string;
    description?: string;
    category?: string;
    quantity?: number;
    min_quantity?: number;
    unit?: string;
    unit_cost?: string;
    reorder_cost?: string;
    storage_location?: string;
    supplier_id?: number;
}
interface Props {
    partsInventory: PartsInventoryItem;
    garages: { id: number; name: string }[];
    partsSuppliers: { id: number; name: string }[];
}

export default function FleetPartsInventoryEdit({ partsInventory, garages, partsSuppliers }: Props) {
    const form = useForm({
        garage_id: partsInventory.garage_id ?? ('' as number | ''),
        part_number: partsInventory.part_number,
        description: partsInventory.description ?? '',
        category: partsInventory.category ?? '',
        quantity: partsInventory.quantity ?? ('' as number | ''),
        min_quantity: partsInventory.min_quantity ?? ('' as number | ''),
        unit: partsInventory.unit ?? '',
        unit_cost: partsInventory.unit_cost ?? '',
        reorder_cost: partsInventory.reorder_cost ?? '',
        storage_location: partsInventory.storage_location ?? '',
        supplier_id: partsInventory.supplier_id ?? ('' as number | ''),
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Parts inventory', href: '/fleet/parts-inventory' },
        { title: 'Edit', href: `/fleet/parts-inventory/${partsInventory.id}/edit` },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit parts inventory item" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild><Link href={`/fleet/parts-inventory/${partsInventory.id}`}>Back</Link></Button>
                    <h1 className="text-2xl font-semibold">Edit parts inventory item</h1>
                </div>
                <form onSubmit={(e) => { e.preventDefault(); form.put(`/fleet/parts-inventory/${partsInventory.id}`); }} className="max-w-xl space-y-4 rounded-lg border p-6">
                    <div className="space-y-2">
                        <Label>Part number</Label>
                        <Input value={form.data.part_number} onChange={e => form.setData('part_number', e.target.value)} required />
                    </div>
                    <div className="space-y-2">
                        <Label>Description</Label>
                        <Input value={form.data.description} onChange={e => form.setData('description', e.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Garage</Label>
                            <select value={form.data.garage_id} onChange={e => form.setData('garage_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                <option value="">—</option>
                                {garages.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label>Supplier</Label>
                            <select value={form.data.supplier_id} onChange={e => form.setData('supplier_id', e.target.value ? Number(e.target.value) : '')} className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm">
                                <option value="">—</option>
                                {partsSuppliers.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Category</Label>
                            <Input value={form.data.category} onChange={e => form.setData('category', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Unit</Label>
                            <Input value={form.data.unit} onChange={e => form.setData('unit', e.target.value)} />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Quantity</Label>
                            <Input type="number" min={0} value={form.data.quantity === '' ? '' : form.data.quantity} onChange={e => form.setData('quantity', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                        <div className="space-y-2">
                            <Label>Min quantity</Label>
                            <Input type="number" min={0} value={form.data.min_quantity === '' ? '' : form.data.min_quantity} onChange={e => form.setData('min_quantity', e.target.value === '' ? '' : Number(e.target.value))} />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Unit cost</Label>
                            <Input type="number" step="any" min={0} value={form.data.unit_cost} onChange={e => form.setData('unit_cost', e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label>Reorder cost</Label>
                            <Input type="number" step="any" min={0} value={form.data.reorder_cost} onChange={e => form.setData('reorder_cost', e.target.value)} />
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Storage location</Label>
                        <Input value={form.data.storage_location} onChange={e => form.setData('storage_location', e.target.value)} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>Update</Button>
                        <Button type="button" variant="outline" asChild><Link href={`/fleet/parts-inventory/${partsInventory.id}`}>Cancel</Link></Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
