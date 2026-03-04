import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Play } from 'lucide-react';
import { useState } from 'react';

interface WorkflowStep {
    type?: string;
    config?: { agent?: string };
}
interface WorkflowDefinitionRecord {
    id: number;
    name: string;
    description?: string | null;
    trigger_type: string;
    trigger_config?: { event?: string } | null;
    is_active: boolean;
    steps?: WorkflowStep[];
}
interface Props {
    workflowDefinition: WorkflowDefinitionRecord;
    executeUrl: string;
}

export default function FleetWorkflowDefinitionsShow({
    workflowDefinition,
    executeUrl,
}: Props) {
    const [running, setRunning] = useState(false);
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/workflow-definitions' },
        { title: 'Workflow definitions', href: '/fleet/workflow-definitions' },
        {
            title: workflowDefinition.name,
            href: `/fleet/workflow-definitions/${workflowDefinition.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – ${workflowDefinition.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        {workflowDefinition.name}
                    </h1>
                    <div className="flex items-center gap-2">
                        {workflowDefinition.is_active && (
                            <Button
                                variant="default"
                                size="sm"
                                disabled={running}
                                onClick={async () => {
                                    setRunning(true);
                                    try {
                                        const res = await fetch(executeUrl, {
                                            method: 'POST',
                                            headers: {
                                                Accept: 'application/json',
                                                'Content-Type':
                                                    'application/json',
                                                'X-CSRF-TOKEN':
                                                    document
                                                        .querySelector(
                                                            'meta[name="csrf-token"]',
                                                        )
                                                        ?.getAttribute(
                                                            'content',
                                                        ) ?? '',
                                                'X-Requested-With':
                                                    'XMLHttpRequest',
                                            },
                                            credentials: 'include',
                                        });
                                        const data = await res
                                            .json()
                                            .catch(() => ({}));
                                        if (res.ok && data?.url)
                                            router.visit(data.url);
                                        else if (res.ok) router.reload();
                                    } finally {
                                        setRunning(false);
                                    }
                                }}
                            >
                                <Play className="mr-1.5 size-4" />
                                {running ? 'Starting…' : 'Run workflow'}
                            </Button>
                        )}
                        <Button variant="outline" asChild>
                            <Link href="/fleet/workflow-definitions">Back</Link>
                        </Button>
                    </div>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">
                            Workflow definition
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Name:</span>{' '}
                            {workflowDefinition.name}
                        </p>
                        {workflowDefinition.description && (
                            <p>
                                <span className="font-medium">
                                    Description:
                                </span>{' '}
                                {workflowDefinition.description}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Trigger type:</span>{' '}
                            {workflowDefinition.trigger_type}
                        </p>
                        <p>
                            <span className="font-medium">Active:</span>{' '}
                            {workflowDefinition.is_active ? 'Yes' : 'No'}
                        </p>
                        {workflowDefinition.trigger_type === 'event' &&
                            workflowDefinition.trigger_config?.event && (
                                <p>
                                    <span className="font-medium">Event:</span>{' '}
                                    <code className="rounded bg-muted px-1 text-xs">
                                        {
                                            workflowDefinition.trigger_config
                                                .event
                                        }
                                    </code>
                                </p>
                            )}
                    </CardContent>
                </Card>
                {workflowDefinition.steps &&
                    workflowDefinition.steps.length > 0 && (
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-base">
                                    Steps (run in order)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="list-inside list-disc space-y-1 text-sm">
                                    {workflowDefinition.steps.map((step, i) => (
                                        <li key={i}>
                                            {(step.type === 'run_ai' ||
                                                step.type === 'ai_agent' ||
                                                step.type === 'run_ai_job') &&
                                            step.config?.agent
                                                ? `Run AI: ${step.config.agent}`
                                                : step.type === 'create_alert'
                                                  ? 'Create alert (from prior step)'
                                                  : step.type ===
                                                      'create_work_order'
                                                    ? 'Create work order (from prior step)'
                                                    : `Step ${i + 1}: ${step.type ?? 'unknown'}`}
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
            </div>
        </AppLayout>
    );
}
