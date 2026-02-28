import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface WorkflowDefinitionRecord {
    id: number;
    name: string;
    description?: string | null;
    trigger_type: string;
    is_active: boolean;
}
interface Props {
    workflowDefinition: WorkflowDefinitionRecord;
}

export default function FleetWorkflowDefinitionsShow({
    workflowDefinition,
}: Props) {
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
                    <h1 className="text-2xl font-semibold">{workflowDefinition.name}</h1>
                    <Button variant="outline" asChild>
                        <Link href="/fleet/workflow-definitions">Back</Link>
                    </Button>
                </div>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Workflow definition</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="font-medium">Name:</span> {workflowDefinition.name}
                        </p>
                        {workflowDefinition.description && (
                            <p>
                                <span className="font-medium">Description:</span>{' '}
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
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
