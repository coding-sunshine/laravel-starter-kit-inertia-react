import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Clock, Send } from 'lucide-react';

interface PushHistory {
    id: number;
    pushable_type: string;
    pushable_id: number;
    channel: string;
    status: string;
    response: string | null;
    created_at: string;
}

interface PushSchedule {
    id: number;
    pushable_type: string;
    pushable_id: number;
    channel: string;
    scheduled_at: string;
    status: string;
}

interface Props {
    push_history: PushHistory[];
    push_schedules: PushSchedule[];
    channels: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Agent Portal', href: '/agent-portal' },
];

const STATUS_COLORS: Record<string, string> = {
    sent: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    pending: 'bg-yellow-100 text-yellow-700',
    scheduled: 'bg-blue-100 text-blue-700',
};

function StatusBadge({ status }: { status: string }) {
    const colorClass = STATUS_COLORS[status] ?? 'bg-gray-100 text-gray-700';
    return (
        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${colorClass}`}>
            {status}
        </span>
    );
}

export default function AgentPortalIndexPage({ push_history, push_schedules, channels }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        pushable_type: 'lot',
        pushable_id: '',
        channel: channels[0] ?? '',
        scheduled_at: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/agent-portal/schedule', {
            onSuccess: () => reset(),
        });
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Agent Control Panel" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4" data-pan="agent-portal-index">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Agent Control Panel</h1>
                    <p className="text-muted-foreground">Manage push notifications and scheduled campaigns</p>
                </div>

                {/* Schedule New Push */}
                <div className="rounded-lg border bg-card p-5">
                    <h2 className="mb-4 text-lg font-semibold">Schedule a Push</h2>
                    <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium">Type</label>
                            <select
                                value={data.pushable_type}
                                onChange={(e) => setData('pushable_type', e.target.value)}
                                className="rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="lot">Lot</option>
                                <option value="project">Project</option>
                            </select>
                            {errors.pushable_type && (
                                <p className="text-xs text-destructive">{errors.pushable_type}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium">ID</label>
                            <input
                                type="number"
                                min="1"
                                value={data.pushable_id}
                                onChange={(e) => setData('pushable_id', e.target.value)}
                                placeholder="e.g. 42"
                                className="rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            {errors.pushable_id && (
                                <p className="text-xs text-destructive">{errors.pushable_id}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium">Channel</label>
                            <select
                                value={data.channel}
                                onChange={(e) => setData('channel', e.target.value)}
                                className="rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                {channels.map((ch) => (
                                    <option key={ch} value={ch}>
                                        {ch}
                                    </option>
                                ))}
                            </select>
                            {errors.channel && (
                                <p className="text-xs text-destructive">{errors.channel}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-1">
                            <label className="text-sm font-medium">Scheduled At</label>
                            <input
                                type="datetime-local"
                                value={data.scheduled_at}
                                onChange={(e) => setData('scheduled_at', e.target.value)}
                                className="rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            {errors.scheduled_at && (
                                <p className="text-xs text-destructive">{errors.scheduled_at}</p>
                            )}
                        </div>

                        <div className="flex items-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                <Send className="h-4 w-4" />
                                Schedule
                            </button>
                        </div>
                    </form>
                </div>

                {/* Scheduled Pushes */}
                <div className="rounded-lg border bg-card">
                    <div className="flex items-center gap-2 border-b px-5 py-4">
                        <Clock className="h-5 w-5 text-muted-foreground" />
                        <h2 className="text-lg font-semibold">Scheduled Pushes</h2>
                        <span className="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                            {push_schedules.length}
                        </span>
                    </div>
                    {push_schedules.length === 0 ? (
                        <p className="p-5 text-sm text-muted-foreground">No scheduled pushes.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-5 py-3 text-left font-medium">Type / ID</th>
                                        <th className="px-5 py-3 text-left font-medium">Channel</th>
                                        <th className="px-5 py-3 text-left font-medium">Scheduled At</th>
                                        <th className="px-5 py-3 text-left font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {push_schedules.map((schedule) => (
                                        <tr key={schedule.id} className="hover:bg-muted/20">
                                            <td className="px-5 py-3">
                                                <span className="font-medium capitalize">{schedule.pushable_type}</span>
                                                <span className="ml-1 text-muted-foreground">#{schedule.pushable_id}</span>
                                            </td>
                                            <td className="px-5 py-3">{schedule.channel}</td>
                                            <td className="px-5 py-3 text-muted-foreground">
                                                {new Date(schedule.scheduled_at).toLocaleString()}
                                            </td>
                                            <td className="px-5 py-3">
                                                <StatusBadge status={schedule.status} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Push History */}
                <div className="rounded-lg border bg-card">
                    <div className="border-b px-5 py-4">
                        <h2 className="text-lg font-semibold">Push History</h2>
                        <p className="text-sm text-muted-foreground">{push_history.length} records</p>
                    </div>
                    {push_history.length === 0 ? (
                        <p className="p-5 text-sm text-muted-foreground">No push history yet.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-5 py-3 text-left font-medium">Type / ID</th>
                                        <th className="px-5 py-3 text-left font-medium">Channel</th>
                                        <th className="px-5 py-3 text-left font-medium">Status</th>
                                        <th className="px-5 py-3 text-left font-medium">Sent At</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {push_history.map((item) => (
                                        <tr key={item.id} className="hover:bg-muted/20">
                                            <td className="px-5 py-3">
                                                <span className="font-medium capitalize">{item.pushable_type}</span>
                                                <span className="ml-1 text-muted-foreground">#{item.pushable_id}</span>
                                            </td>
                                            <td className="px-5 py-3">{item.channel}</td>
                                            <td className="px-5 py-3">
                                                <StatusBadge status={item.status} />
                                            </td>
                                            <td className="px-5 py-3 text-muted-foreground">
                                                {new Date(item.created_at).toLocaleString()}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppSidebarLayout>
    );
}
