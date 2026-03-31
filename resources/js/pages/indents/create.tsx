import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface PowerPlant {
    name: string;
    code: string;
}

interface Props {
    sidings: Siding[];
    power_plants: PowerPlant[];
}

const selectClassName = cn(
    'border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs transition-[color,box-shadow] outline-none',
    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
);

export default function IndentsCreate({ sidings, power_plants }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'E-Demand', href: '/indents' },
        { title: 'Create e-demand', href: '/indents/create' },
    ];

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = new FormData(form);
        router.post('/indents', formData, { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create e-demand" />
            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="flex items-center gap-2 text-2xl font-semibold tracking-tight">
                            <Plus className="size-6 text-muted-foreground" />
                            Create e-demand
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Enter e-demand details. A linked rake will be created automatically
                            after saving.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        data-pan="indents-create-cancel"
                        onClick={() => router.visit('/indents')}
                    >
                        Back to e-demand
                    </Button>
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="space-y-6"
                    encType="multipart/form-data"
                >
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Rake details</CardTitle>
                            <CardDescription>
                                A linked rake is created right after saving this e-demand.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="rake_number">Rake number</Label>
                                <Input
                                    id="rake_number"
                                    name="rake_number"
                                    placeholder="Optional (e.g. Rake Sq. Number)"
                                />
                                <InputError message={errors?.rake_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="rake_priority_number">
                                    Priority number
                                </Label>
                                <Input
                                    id="rake_priority_number"
                                    name="rake_priority_number"
                                    type="number"
                                    min={0}
                                    step={1}
                                    placeholder="Optional (default is new e-demand id)"
                                />
                                <InputError
                                    message={errors?.rake_priority_number}
                                />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="expected_loading_date">
                                    Loading date (rake)
                                </Label>
                                <Input
                                    id="expected_loading_date"
                                    name="expected_loading_date"
                                    type="date"
                                />
                                <InputError
                                    message={errors?.expected_loading_date}
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <FileText className="size-4" />
                                Location & status
                            </CardTitle>
                            <CardDescription>
                                Siding and official e-demand / forwarding note number
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="siding_id">Siding *</Label>
                                <select
                                    id="siding_id"
                                    name="siding_id"
                                    required
                                    defaultValue=""
                                    className={selectClassName}
                                >
                                    <option value="" disabled>
                                        Select siding
                                    </option>
                                    {sidings.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name} ({s.code})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors?.siding_id} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="indent_number">
                                    E-Demand / forwarding note number
                                </Label>
                                <Input
                                    id="indent_number"
                                    name="indent_number"
                                    placeholder="e.g. 302.001"
                                />
                                <InputError message={errors?.indent_number} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Demand & quantities
                            </CardTitle>
                            <CardDescription>
                                Stock type, units, and metric tonnes (as on e-Demand slip)
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="grid gap-2 sm:col-span-2 lg:col-span-1">
                                <Label htmlFor="demanded_stock">
                                    Demanded stock (wagon / stock type)
                                </Label>
                                <Input
                                    id="demanded_stock"
                                    name="demanded_stock"
                                    placeholder="e.g. BOBRN"
                                />
                                <InputError message={errors?.demanded_stock} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="total_units">Total units</Label>
                                <Input
                                    id="total_units"
                                    name="total_units"
                                    type="number"
                                    min={0}
                                    step={1}
                                    placeholder="Wagons"
                                />
                                <InputError message={errors?.total_units} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="target_quantity_mt">
                                    Target quantity (MT)
                                </Label>
                                <Input
                                    id="target_quantity_mt"
                                    name="target_quantity_mt"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                />
                                <InputError message={errors?.target_quantity_mt} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                Railway references
                            </CardTitle>
                            <CardDescription>
                                e-Demand reference ID, FNR, and destination power plant
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="e_demand_reference_id">
                                    e-Demand reference ID
                                </Label>
                                <Input
                                    id="e_demand_reference_id"
                                    name="e_demand_reference_id"
                                />
                                <InputError
                                    message={errors?.e_demand_reference_id}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="fnr_number">FNR number</Label>
                                <Input id="fnr_number" name="fnr_number" />
                                <InputError message={errors?.fnr_number} />
                            </div>
                            <div className="grid gap-2 sm:col-span-2">
                                <Label htmlFor="destination">
                                    Destination (power plant)
                                </Label>
                                <select
                                    id="destination"
                                    name="destination"
                                    defaultValue=""
                                    className={selectClassName}
                                >
                                    <option value="">Select power plant</option>
                                    {power_plants.map((p) => (
                                        <option key={p.code} value={p.code}>
                                            {p.name} ({p.code})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors?.destination} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Dates</CardTitle>
                            <CardDescription>
                                Loading date and optional demand timestamp
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="indent_at">
                                    E-Demand date &amp; time *
                                </Label>
                                <Input
                                    id="indent_at"
                                    name="indent_at"
                                    type="datetime-local"
                                    required
                                />
                                <InputError message={errors?.indent_at} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">
                                E-Demand PDF & notes
                            </CardTitle>
                            <CardDescription>
                                Attach a confirmation PDF (optional)
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="pdf">
                                    e-Demand confirmation (PDF)
                                </Label>
                                <Input
                                    id="pdf"
                                    name="pdf"
                                    type="file"
                                    accept=".pdf,application/pdf"
                                />
                                <InputError message={errors?.pdf} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="remarks">Remarks</Label>
                                <textarea
                                    id="remarks"
                                    name="remarks"
                                    rows={4}
                                    className={cn(
                                        'border-input bg-background placeholder:text-muted-foreground min-h-[100px] w-full rounded-md border px-3 py-2 text-sm shadow-xs',
                                        'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] outline-none',
                                    )}
                                />
                                <InputError message={errors?.remarks} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" data-pan="indents-create-submit">
                            Create e-demand
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            data-pan="indents-create-cancel"
                            onClick={() => router.visit('/indents')}
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
