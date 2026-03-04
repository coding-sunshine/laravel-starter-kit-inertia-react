import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface TyreInventoryItem {
    id: number;
    size: string;
    brand?: string;
    pattern?: string;
    category?: string;
    quantity?: number;
    min_quantity?: number;
    unit_cost?: string;
    storage_location?: string;
    is_active?: boolean;
}
interface Props {
    tyreInventory: TyreInventoryItem;
}

export default function FleetTyreInventoryEdit({ tyreInventory }: Props) {
    const form = useForm({
        size: tyreInventory.size,
        brand: tyreInventory.brand ?? '',
        pattern: tyreInventory.pattern ?? '',
        category: tyreInventory.category ?? '',
        quantity: tyreInventory.quantity ?? ('' as number | ''),
        min_quantity: tyreInventory.min_quantity ?? ('' as number | ''),
        unit_cost: tyreInventory.unit_cost ?? '',
        storage_location: tyreInventory.storage_location ?? '',
        is_active: tyreInventory.is_active ?? true,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Tyre inventory', href: '/fleet/tyre-inventory' },
        {
            title: 'Edit',
            href: `/fleet/tyre-inventory/${tyreInventory.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit tyre inventory item" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={`/fleet/tyre-inventory/${tyreInventory.id}`}
                        >
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit tyre inventory item
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(`/fleet/tyre-inventory/${tyreInventory.id}`);
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Size</Label>
                            <Input
                                value={form.data.size}
                                onChange={(e) =>
                                    form.setData('size', e.target.value)
                                }
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Brand</Label>
                            <Input
                                value={form.data.brand}
                                onChange={(e) =>
                                    form.setData('brand', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Pattern</Label>
                            <Input
                                value={form.data.pattern}
                                onChange={(e) =>
                                    form.setData('pattern', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Category</Label>
                            <Input
                                value={form.data.category}
                                onChange={(e) =>
                                    form.setData('category', e.target.value)
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Quantity</Label>
                            <Input
                                type="number"
                                min={0}
                                value={
                                    form.data.quantity === ''
                                        ? ''
                                        : form.data.quantity
                                }
                                onChange={(e) =>
                                    form.setData(
                                        'quantity',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Min quantity</Label>
                            <Input
                                type="number"
                                min={0}
                                value={
                                    form.data.min_quantity === ''
                                        ? ''
                                        : form.data.min_quantity
                                }
                                onChange={(e) =>
                                    form.setData(
                                        'min_quantity',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>Unit cost</Label>
                            <Input
                                type="number"
                                step="any"
                                min={0}
                                value={form.data.unit_cost}
                                onChange={(e) =>
                                    form.setData('unit_cost', e.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Storage location</Label>
                            <Input
                                value={form.data.storage_location}
                                onChange={(e) =>
                                    form.setData(
                                        'storage_location',
                                        e.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={form.data.is_active}
                            onChange={(e) =>
                                form.setData('is_active', e.target.checked)
                            }
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Update
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/tyre-inventory/${tyreInventory.id}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
