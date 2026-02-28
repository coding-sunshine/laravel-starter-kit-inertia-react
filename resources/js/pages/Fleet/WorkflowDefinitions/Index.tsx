import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { GitBranch, Pencil, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface WorkflowDefinitionRecord {
    id: number;
    name: string;
    trigger_type: string;
    is_active: boolean;
}
interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
interface Props {
    workflowDefinitions: { data: WorkflowDefinitionRecord[]; links: PaginationLink[] };
    triggerTypes: { value: string; name: string }[];
}

export default function FleetWorkflowDefinitionsIndex({
    workflowDefinitions,
    triggerTypes,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet/workflow-definitions' },
        { title: 'Workflow definitions', href: '/fleet/workflow-definitions' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Workflow definitions" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Workflow definitions</h1>
                    <Button asChild>
                        <Link href="/fleet/workflow-definitions/create">
                            <Plus className="mr-2 size-4" />
                            New
                        </Link>
                    </Button>
                </div>
                {workflowDefinitions.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <GitBranch className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No workflow definitions yet.
                        </p>
                        <Button asChild className="mt-4">
                            <Link href="/fleet/workflow-definitions/create">
                                Create workflow definition
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="rounded-md border">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="p-3 text-left font-medium">Name</th>
                                        <th className="p-3 text-left font-medium">Trigger type</th>
                                        <th className="p-3 text-left font-medium">Active</th>
                                        <th className="p-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {workflowDefinitions.data.map((row) => (
                                        <tr key={row.id} className="border-b last:border-0">
                                            <td className="p-3">
                                                <Link
                                                    href={`/fleet/workflow-definitions/${row.id}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {row.name}
                                                </Link>
                                            </td>
                                            <td className="p-3">{row.trigger_type}</td>
                                            <td className="p-3">
                                                {row.is_active ? 'Yes' : 'No'}
                                            </td>
                                            <td className="p-3 text-right">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={`/fleet/workflow-definitions/${row.id}`}>
                                                        View
                                                    </Link>
                                                </Button>
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link
                                                        href={`/fleet/workflow-definitions/${row.id}/edit`}
                                                    >
                                                        <Pencil className="ml-1 size-3.5" />
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/fleet/workflow-definitions/${row.id}`}
                                                    method="delete"
                                                    className="ml-2 inline"
                                                    onSubmit={(e) => {
                                                        if (!confirm('Delete?')) e.preventDefault();
                                                    }}
                                                >
                                                    <Button type="submit" variant="ghost" size="sm">
                                                        <Trash2 className="size-3.5 text-destructive" />
                                                    </Button>
                                                </Form>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {workflowDefinitions.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {workflowDefinitions.links.map((link, i) => (
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
