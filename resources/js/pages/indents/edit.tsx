import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';

interface Siding {
    id: number;
    name: string;
    code: string;
}

interface Indent {
    id: number;
    indent_number: string;
    target_quantity_mt: string;
    allocated_quantity_mt: string;
    state: string;
    indent_date: string;
    required_by_date: string | null;
    remarks: string | null;
    e_demand_reference_id: string | null;
    fnr_number: string | null;
    siding_id: number;
}

interface Props {
    indent: Indent;
    sidings: Siding[];
}

export default function IndentsEdit({ indent, sidings }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Indents', href: '/indents' },
        { title: indent.indent_number, href: `/indents/${indent.id}` },
        { title: 'Edit', href: `/indents/${indent.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = new FormData(form);
        formData.append('_method', 'PUT');
        router.post(`/indents/${indent.id}`, formData, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit indent ${indent.indent_number}`} />
            <div className="space-y-6">
                <h2 className="text-lg font-medium">
                    Edit indent {indent.indent_number}
                </h2>
                <form
                    onSubmit={handleSubmit}
                    className="max-w-lg space-y-4"
                    encType="multipart/form-data"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="siding_id">Siding *</Label>
                        <select
                            id="siding_id"
                            name="siding_id"
                            required
                            defaultValue={indent.siding_id}
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            {sidings.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.name} ({s.code})
                                </option>
                            ))}
                        </select>
                        <InputError message={errors?.siding_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="indent_number">Indent number *</Label>
                        <Input
                            id="indent_number"
                            name="indent_number"
                            required
                            defaultValue={indent.indent_number}
                            className="text-sm"
                        />
                        <InputError message={errors?.indent_number} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="target_quantity_mt">
                                Target quantity (MT) *
                            </Label>
                            <Input
                                id="target_quantity_mt"
                                name="target_quantity_mt"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                defaultValue={indent.target_quantity_mt}
                                className="text-sm"
                            />
                            <InputError message={errors?.target_quantity_mt} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="allocated_quantity_mt">
                                Allocated (MT)
                            </Label>
                            <Input
                                id="allocated_quantity_mt"
                                name="allocated_quantity_mt"
                                type="number"
                                step="0.01"
                                min="0"
                                defaultValue={indent.allocated_quantity_mt}
                                className="text-sm"
                            />
                            <InputError
                                message={errors?.allocated_quantity_mt}
                            />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="state">State</Label>
                        <select
                            id="state"
                            name="state"
                            defaultValue={indent.state}
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            <option value="pending">Pending</option>
                            <option value="allocated">Allocated</option>
                            <option value="partial">Partial</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <InputError message={errors?.state} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="indent_date">Indent date *</Label>
                            <Input
                                id="indent_date"
                                name="indent_date"
                                type="date"
                                required
                                defaultValue={
                                    indent.indent_date?.slice(0, 10) ?? ''
                                }
                                className="text-sm"
                            />
                            <InputError message={errors?.indent_date} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="required_by_date">
                                Required by date
                            </Label>
                            <Input
                                id="required_by_date"
                                name="required_by_date"
                                type="date"
                                defaultValue={
                                    indent.required_by_date?.slice(0, 10) ?? ''
                                }
                                className="text-sm"
                            />
                            <InputError message={errors?.required_by_date} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="e_demand_reference_id">
                            e-Demand reference ID
                        </Label>
                        <Input
                            id="e_demand_reference_id"
                            name="e_demand_reference_id"
                            defaultValue={indent.e_demand_reference_id ?? ''}
                            className="text-sm"
                        />
                        <InputError message={errors?.e_demand_reference_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="fnr_number">FNR number</Label>
                        <Input
                            id="fnr_number"
                            name="fnr_number"
                            defaultValue={indent.fnr_number ?? ''}
                            className="text-sm"
                        />
                        <InputError message={errors?.fnr_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="indent_pdf">
                            e-Demand confirmation (PDF) – replace existing
                        </Label>
                        <Input
                            id="indent_pdf"
                            name="pdf"
                            type="file"
                            accept=".pdf,application/pdf"
                            className="text-sm"
                        />
                        <InputError message={errors?.pdf} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="remarks">Remarks</Label>
                        <textarea
                            id="remarks"
                            name="remarks"
                            rows={2}
                            defaultValue={indent.remarks ?? ''}
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        />
                        <InputError message={errors?.remarks} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit">Update indent</Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.visit(`/indents/${indent.id}`)
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
