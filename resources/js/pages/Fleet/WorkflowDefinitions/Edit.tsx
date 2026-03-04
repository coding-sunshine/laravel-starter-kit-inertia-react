import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Option {
    value: string;
    name: string;
}
interface WorkflowDefinitionRecord {
    id: number;
    name: string;
    description?: string | null;
    trigger_type: string;
    trigger_config?: unknown;
    steps?: unknown;
    is_active: boolean;
}
interface Props {
    workflowDefinition: WorkflowDefinitionRecord;
    triggerTypes: Option[];
}

export default function FleetWorkflowDefinitionsEdit({
    workflowDefinition,
    triggerTypes,
}: Props) {
    const form = useForm(
        {
            name: workflowDefinition.name,
            description: workflowDefinition.description ?? '',
            trigger_type: workflowDefinition.trigger_type,
            trigger_config: workflowDefinition.trigger_config ?? undefined,
            steps: workflowDefinition.steps ?? undefined,
            stepsJson:
                typeof workflowDefinition.steps === 'object'
                    ? JSON.stringify(workflowDefinition.steps, null, 2)
                    : typeof workflowDefinition.steps === 'string'
                      ? workflowDefinition.steps
                      : '[]',
            is_active: workflowDefinition.is_active,
        },
        {
            transform: (data) => {
                let steps: unknown[];
                try {
                    const raw = data.stepsJson ?? '[]';
                    steps =
                        typeof raw === 'string'
                            ? JSON.parse(raw)
                            : Array.isArray(raw)
                              ? raw
                              : [];
                } catch {
                    steps = [];
                }
                return { ...data, steps, stepsJson: undefined };
            },
        },
    );
    const { data, setData, processing, errors } = form;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/workflow-definitions' },
        { title: 'Workflow definitions', href: '/fleet/workflow-definitions' },
        {
            title: workflowDefinition.name,
            href: `/fleet/workflow-definitions/${workflowDefinition.id}`,
        },
        {
            title: 'Edit',
            href: `/fleet/workflow-definitions/${workflowDefinition.id}/edit`,
        },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/fleet/workflow-definitions/${workflowDefinition.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Edit ${workflowDefinition.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">
                    Edit workflow definition
                </h1>
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
                            <p className="mt-1 text-sm text-destructive">
                                {errors.name}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <Label htmlFor="trigger_type">Trigger type *</Label>
                        <select
                            id="trigger_type"
                            value={data.trigger_type}
                            onChange={(e) =>
                                setData('trigger_type', e.target.value)
                            }
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            {triggerTypes.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.name}
                                </option>
                            ))}
                        </select>
                        {errors.trigger_type && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.trigger_type}
                            </p>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="trigger_config">
                            Trigger config (JSON, optional)
                        </Label>
                        <textarea
                            id="trigger_config"
                            value={
                                typeof data.trigger_config === 'object' &&
                                data.trigger_config !== null
                                    ? JSON.stringify(
                                          data.trigger_config,
                                          null,
                                          2,
                                      )
                                    : data.trigger_config &&
                                        typeof data.trigger_config === 'string'
                                      ? data.trigger_config
                                      : '{}'
                            }
                            onChange={(e) => {
                                try {
                                    setData(
                                        'trigger_config',
                                        e.target.value
                                            ? JSON.parse(e.target.value)
                                            : {},
                                    );
                                } catch {
                                    setData('trigger_config', {});
                                }
                            }}
                            className="mt-1 flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm"
                            placeholder='{"event": "ai.compliance_prediction.completed"} or {"frequency": "daily"}'
                        />
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) =>
                                setData('is_active', e.target.checked)
                            }
                            className="size-4 rounded border-input"
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>
                    <div>
                        <Label htmlFor="steps">Steps (JSON array)</Label>
                        <textarea
                            id="steps"
                            value={data.stepsJson ?? '[]'}
                            onChange={(e) =>
                                setData('stepsJson', e.target.value)
                            }
                            className="mt-1 flex min-h-[120px] w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm"
                            placeholder="[]"
                        />
                        {errors.steps && (
                            <p className="mt-1 text-sm text-destructive">
                                {errors.steps}
                            </p>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/fleet/workflow-definitions/${workflowDefinition.id}`}
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
