import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

interface Rake {
    id: number;
    rake_number: string;
}

interface PowerPlant {
    id: number;
    name: string;
    code: string;
}

interface Props {
    rakes: Rake[];
    powerPlants: PowerPlant[];
}

export default function PowerPlantReceiptsCreate({
    rakes,
    powerPlants,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Reconciliation', href: '/reconciliation' },
        {
            title: 'Power plant receipts',
            href: '/reconciliation/power-plant-receipts',
        },
        {
            title: 'Add receipt',
            href: '/reconciliation/power-plant-receipts/create',
        },
    ];

    const form = useForm({
        rake_id: '',
        power_plant_id: '',
        receipt_date: '',
        weight_mt: '',
        rr_reference: '',
        status: 'pending',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/reconciliation/power-plant-receipts');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add power plant receipt" />
            <div className="space-y-6">
                <h2 className="text-lg font-medium">Add power plant receipt</h2>
                <form onSubmit={handleSubmit} className="max-w-md space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="rake_id">Rake *</Label>
                        <select
                            id="rake_id"
                            required
                            value={form.data.rake_id}
                            onChange={(e) =>
                                form.setData('rake_id', e.target.value)
                            }
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            <option value="">Select rake</option>
                            {rakes.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.rake_number}
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.rake_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="power_plant_id">Power plant *</Label>
                        <select
                            id="power_plant_id"
                            required
                            value={form.data.power_plant_id}
                            onChange={(e) =>
                                form.setData('power_plant_id', e.target.value)
                            }
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            <option value="">Select power plant</option>
                            {powerPlants.map((pp) => (
                                <option key={pp.id} value={pp.id}>
                                    {pp.name} ({pp.code})
                                </option>
                            ))}
                        </select>
                        <InputError message={form.errors.power_plant_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="receipt_date">Receipt date *</Label>
                        <Input
                            id="receipt_date"
                            type="date"
                            required
                            value={form.data.receipt_date}
                            onChange={(e) =>
                                form.setData('receipt_date', e.target.value)
                            }
                            className="text-sm"
                        />
                        <InputError message={form.errors.receipt_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="weight_mt">Weight (MT) *</Label>
                        <Input
                            id="weight_mt"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={form.data.weight_mt}
                            onChange={(e) =>
                                form.setData('weight_mt', e.target.value)
                            }
                            className="text-sm"
                        />
                        <InputError message={form.errors.weight_mt} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="rr_reference">RR reference</Label>
                        <Input
                            id="rr_reference"
                            value={form.data.rr_reference}
                            onChange={(e) =>
                                form.setData('rr_reference', e.target.value)
                            }
                            className="text-sm"
                        />
                        <InputError message={form.errors.rr_reference} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                form.get('/reconciliation/power-plant-receipts')
                            }
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
