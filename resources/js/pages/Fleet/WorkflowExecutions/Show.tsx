import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface WorkflowExecutionRecord {
    id: number;
    workflow_definition_id: number;
    status: string;
    started_at: string;
    completed_at?: string | null;
    workflow_definition?: { id: number; name: string };
}
interface Props {
    workflowExecution: WorkflowExecutionRecord;
}

export default function FleetWorkflowExecutionsShow({
    workflowExecution,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/workflow-executions' },
        { title: 'Workflow executions', href: '/fleet/workflow-executions' },
        {
            title: `Execution #${workflowExecution.id}`,
            href: `/fleet/workflow-executions/${workflowExecution.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Fleet – Workflow execution #${workflowExecution.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">
                        Workflow execution #{workflowExecution.id}
                    </h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/workflow-executions">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Execution details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">ID:</span> {workflowExecution.id}
                        </p>
                        <p>
                            <span className="font-medium">Workflow definition:</span>{' '}
                            {workflowExecution.workflow_definition ? (
                                <Link
                                    href={`/fleet/workflow-definitions/${workflowExecution.workflow_definition.id}`}
                                    className="underline"
                                >
                                    {workflowExecution.workflow_definition.name}
                                </Link>
                            ) : (
                                workflowExecution.workflow_definition_id
                            )}
                        </p>
                        <p>
                            <span className="font-medium">Status:</span> {workflowExecution.status}
                        </p>
                        <p>
                            <span className="font-medium">Started at:</span>{' '}
                            {workflowExecution.started_at
                                ? new Date(workflowExecution.started_at).toLocaleString()
                                : '—'}
                        </p>
                        <p>
                            <span className="font-medium">Completed at:</span>{' '}
                            {workflowExecution.completed_at
                                ? new Date(workflowExecution.completed_at).toLocaleString()
                                : '—'}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
