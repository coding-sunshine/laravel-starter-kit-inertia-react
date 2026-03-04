import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Template {
    id: number;
    name: string;
    code?: string;
    check_type: string;
    category?: string;
    workflow_route?: string;
    completion_percentage_threshold?: number;
    is_active: boolean;
    checklist?: { label?: string; result_type?: string }[];
}
interface Props {
    vehicleCheckTemplate: Template;
    checkTypes: { value: string; name: string }[];
}

export default function VehicleCheckTemplatesEdit({
    vehicleCheckTemplate,
    checkTypes,
}: Props) {
    const form = useForm({
        name: vehicleCheckTemplate.name,
        code: vehicleCheckTemplate.code ?? '',
        check_type: vehicleCheckTemplate.check_type,
        category: vehicleCheckTemplate.category ?? '',
        checklist: vehicleCheckTemplate.checklist ?? [],
        workflow_route: vehicleCheckTemplate.workflow_route ?? '',
        completion_percentage_threshold:
            (vehicleCheckTemplate.completion_percentage_threshold ?? '') as
                | number
                | '',
        is_active: vehicleCheckTemplate.is_active,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        {
            title: 'Vehicle check templates',
            href: '/fleet/vehicle-check-templates',
        },
        {
            title: 'Edit',
            href: `/fleet/vehicle-check-templates/${vehicleCheckTemplate.id}/edit`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Edit vehicle check template" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={`/fleet/vehicle-check-templates/${vehicleCheckTemplate.id}`}
                        >
                            Back
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Edit vehicle check template
                    </h1>
                </div>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        form.put(
                            `/fleet/vehicle-check-templates/${vehicleCheckTemplate.id}`,
                        );
                    }}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    <div className="space-y-2">
                        <Label>Name *</Label>
                        <Input
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Code</Label>
                        <Input
                            value={form.data.code}
                            onChange={(e) =>
                                form.setData('code', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Check type *</Label>
                        <select
                            required
                            value={form.data.check_type}
                            onChange={(e) =>
                                form.setData('check_type', e.target.value)
                            }
                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {checkTypes.map((c) => (
                                <option key={c.value} value={c.value}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
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
                    <div className="space-y-2">
                        <Label>Workflow route</Label>
                        <Input
                            value={form.data.workflow_route}
                            onChange={(e) =>
                                form.setData('workflow_route', e.target.value)
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label>Completion % threshold</Label>
                        <Input
                            type="number"
                            min={0}
                            max={100}
                            value={
                                form.data.completion_percentage_threshold || ''
                            }
                            onChange={(e) =>
                                form.setData(
                                    'completion_percentage_threshold',
                                    e.target.value
                                        ? Number(e.target.value)
                                        : '',
                                )
                            }
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={form.data.is_active}
                            onChange={(e) =>
                                form.setData('is_active', e.target.checked)
                            }
                            className="rounded border-input"
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link
                                href={`/fleet/vehicle-check-templates/${vehicleCheckTemplate.id}`}
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
