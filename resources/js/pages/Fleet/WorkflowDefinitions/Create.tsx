import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Option {
    value: string;
    name: string;
}
interface Props {
    triggerTypes: Option[];
}

export default function FleetWorkflowDefinitionsCreate({ triggerTypes }: Props) {
    const form = useForm({
        name: '',
        description: '',
        trigger_type: triggerTypes[0]?.value ?? '',
        trigger_config: undefined as unknown,
        steps: [] as unknown[],
        is_active: true,
    });
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/workflow-definitions' },
        { title: 'Workflow definitions', href: '/fleet/workflow-definitions' },
        { title: 'Create', href: '/fleet/workflow-definitions/create' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/fleet/workflow-definitions');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – New workflow definition" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">New workflow definition</h1>
                <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
                    <div>
                        <Label htmlFor="name">Name *</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-destructive">{errors.name}</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-destructive">{errors.description}</p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="trigger_type">Trigger type *</Label>
                        <select
                            id="trigger_type"
                            value={data.trigger_type}
                            onChange={(e) => setData('trigger_type', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {triggerTypes.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                        {errors.trigger_type && (
                            <p className="mt-1 text-sm text-destructive">{errors.trigger_type}</p>
                        )}
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="size-4 rounded border-input"
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div>
                        <Label htmlFor="steps">Steps (JSON array)</Label>
                        <textarea
                            id="steps"
                            value={Array.isArray(data.steps) ? JSON.stringify(data.steps, null, 2) : '[]'}
                            onChange={(e) => {
                                try {
                                    setData('steps', e.target.value ? JSON.parse(e.target.value) : []);
                                } catch {
                                    setData('steps', []);
                                }
                            }}
                            className="mt-1 flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm"
                            placeholder="[]"
                        />
                    </div>
                    {errors.is_active && (
                        <p className="text-sm text-destructive">{errors.is_active}</p>
                    )}
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Create workflow definition
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href="/fleet/workflow-definitions">Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
