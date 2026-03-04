import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface Talk {
    id: number;
    topic: string;
    content?: string;
    scheduled_date?: string;
    scheduled_time?: string;
    location?: string;
    attendance_count: number;
    status: string;
    presenter?: { id: number; name: string };
}
interface Props {
    toolboxTalk: Talk;
}

export default function ToolboxTalksShow({ toolboxTalk }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Toolbox talks', href: '/fleet/toolbox-talks' },
        {
            title: toolboxTalk.topic,
            href: `/fleet/toolbox-talks/${toolboxTalk.id}`,
        },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Toolbox talk" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">
                        {toolboxTalk.topic}
                    </h1>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/fleet/toolbox-talks/${toolboxTalk.id}/edit`}
                            >
                                Edit
                            </Link>
                        </Button>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/fleet/toolbox-talks">Back</Link>
                        </Button>
                    </div>
                </div>
                <dl className="max-w-md space-y-2 rounded-lg border p-6">
                    <div>
                        <dt className="text-sm text-muted-foreground">Topic</dt>
                        <dd className="font-medium">{toolboxTalk.topic}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Presenter
                        </dt>
                        <dd>{toolboxTalk.presenter?.name ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Scheduled date
                        </dt>
                        <dd>{toolboxTalk.scheduled_date ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Scheduled time
                        </dt>
                        <dd>{toolboxTalk.scheduled_time ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Location
                        </dt>
                        <dd>{toolboxTalk.location ?? '—'}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Attendance count
                        </dt>
                        <dd>{toolboxTalk.attendance_count}</dd>
                    </div>
                    <div>
                        <dt className="text-sm text-muted-foreground">
                            Status
                        </dt>
                        <dd>{toolboxTalk.status}</dd>
                    </div>
                    {toolboxTalk.content && (
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Content
                            </dt>
                            <dd className="whitespace-pre-wrap">
                                {toolboxTalk.content}
                            </dd>
                        </div>
                    )}
                </dl>
            </div>
        </AppLayout>
    );
}
