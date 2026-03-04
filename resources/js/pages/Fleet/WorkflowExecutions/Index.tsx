import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Play, PlayCircle } from 'lucide-react';

interface WorkflowExecutionRecord {
    id: number;
    workflow_definition_id: number;
    status: string;
    started_at: string;
    completed_at?: string | null;
    workflow_definition?: { id: number; name: string };
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Props {
    workflowExecutions: {
        data: WorkflowExecutionRecord[];
        links: PaginationLink[];
    };
    filters: { workflow_definition_id?: string };
}

export default function FleetWorkflowExecutionsIndex({
    workflowExecutions,
    filters,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/workflow-executions' },
        { title: 'Workflow executions', href: '/fleet/workflow-executions' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Workflow executions" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Workflow executions</h1>
                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>Workflow definition ID</Label>
                        <input
                            type="number"
                            name="workflow_definition_id"
                            defaultValue={filters.workflow_definition_id ?? ''}
                            placeholder="Filter by definition ID"
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        />
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {workflowExecutions.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <PlayCircle className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No workflow executions yet.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">
                                            ID
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Workflow definition
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Started at
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Completed at
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {workflowExecutions.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">{row.id}</td>
                                            <td className="p-3">
                                                {row.workflow_definition ? (
                                                    <Link
                                                        href={`/fleet/workflow-definitions/${row.workflow_definition.id}`}
                                                        className="underline"
                                                    >
                                                        {
                                                            row
                                                                .workflow_definition
                                                                .name
                                                        }
                                                    </Link>
                                                ) : (
                                                    row.workflow_definition_id
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {row.status}
                                            </td>
                                            <td className="p-3">
                                                {row.started_at
                                                    ? new Date(
                                                          row.started_at,
                                                      ).toLocaleString()
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {row.completed_at
                                                    ? new Date(
                                                          row.completed_at,
                                                      ).toLocaleString()
                                                    : '—'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/workflow-executions/${row.id}`}
                                                    >
                                                        <Play className="mr-1 size-3.5" />
                                                        View
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {workflowExecutions.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {workflowExecutions.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        className={`rounded border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
