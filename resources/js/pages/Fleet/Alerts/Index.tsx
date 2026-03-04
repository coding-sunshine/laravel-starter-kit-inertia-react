import { FleetPageHeader } from '@/components/fleet';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Bell, Bot, CheckCircle, Settings } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

interface Row {
    id: number;
    title: string;
    alert_type: string;
    severity: string;
    status: string;
    triggered_at: string;
    entity_type?: string | null;
    entity_id?: number | null;
    entity_label?: string | null;
}
interface Props {
    alerts: {
        data: Row[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: Record<string, string>;
    statuses: { value: string; name: string }[];
    severities: { value: string; name: string }[];
    alertTypes: { value: string; name: string }[];
}

export default function FleetAlertsIndex({
    alerts,
    filters,
    statuses,
    severities,
    alertTypes,
}: Props) {
    const [nlQuery, setNlQuery] = useState('');
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const allIds = useMemo(() => alerts.data.map((r) => r.id), [alerts.data]);
    const allSelected =
        allIds.length > 0 && allIds.every((id) => selectedIds.has(id));
    const toggleOne = useCallback((id: number) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    }, []);
    const toggleAll = useCallback(() => {
        if (allSelected) setSelectedIds(new Set());
        else setSelectedIds(new Set(allIds));
    }, [allSelected, allIds]);
    const assistantPrompt = nlQuery.trim()
        ? `Find alerts: ${nlQuery.trim()}`
        : 'List and filter alerts.';
    const assistantHref = `/fleet/assistant?prompt=${encodeURIComponent(assistantPrompt)}`;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Alerts', href: '/fleet/alerts' },
    ];
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Alerts" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <FleetPageHeader
                        title="Alerts"
                        description="Status, severity, and entity. Acknowledge or filter by status/type."
                        action={
                            <div className="flex flex-wrap items-center gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={assistantHref}>
                                        <Bot className="mr-2 size-4" />
                                        Ask assistant
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link
                                        href="/fleet/alert-preferences"
                                        className="gap-2"
                                    >
                                        <Settings className="size-4" />
                                        Alert preferences
                                    </Link>
                                </Button>
                            </div>
                        }
                    />
                </div>
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label className="text-sm text-muted-foreground">
                        Describe what you&apos;re looking for:
                    </label>
                    <div className="flex flex-1 flex-wrap items-center gap-2">
                        <Input
                            placeholder="e.g. unacknowledged high severity alerts"
                            value={nlQuery}
                            onChange={(e) => setNlQuery(e.target.value)}
                            className="max-w-xs"
                        />
                        <Button size="sm" variant="secondary" asChild>
                            <Link href={assistantHref}>
                                <Bot className="mr-1 size-3.5" />
                                Find with AI
                            </Link>
                        </Button>
                    </div>
                </div>
                {selectedIds.size > 0 && (
                    <div className="flex flex-wrap items-center gap-2 rounded-lg border bg-muted/30 px-4 py-2">
                        <span className="text-sm text-muted-foreground">
                            {selectedIds.size} selected
                        </span>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            onClick={() => {
                                router.post(
                                    '/fleet/alerts/bulk-acknowledge',
                                    { ids: Array.from(selectedIds) },
                                    {
                                        preserveScroll: true,
                                        onSuccess: () =>
                                            setSelectedIds(new Set()),
                                    },
                                );
                            }}
                        >
                            <CheckCircle className="mr-2 size-4" />
                            Acknowledge selected
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setSelectedIds(new Set())}
                        >
                            Clear
                        </Button>
                    </div>
                )}
                <form
                    method="get"
                    className="flex flex-wrap items-end gap-4 rounded-lg border p-4"
                >
                    <div className="space-y-1">
                        <Label>Status</Label>
                        <select
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Type</Label>
                        <select
                            name="alert_type"
                            defaultValue={filters.alert_type ?? ''}
                            className="h-9 w-48 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {alertTypes.map((t) => (
                                <option key={t.value} value={t.value}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="space-y-1">
                        <Label>Severity</Label>
                        <select
                            name="severity"
                            defaultValue={filters.severity ?? ''}
                            className="h-9 w-40 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                        >
                            <option value="">All</option>
                            {severities.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <Button type="submit" variant="secondary" size="sm">
                        Filter
                    </Button>
                </form>
                {alerts.data.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-16 text-center">
                        <Bell className="mx-auto size-10 text-muted-foreground" />
                        <p className="mt-2 text-sm text-muted-foreground">
                            No alerts.
                        </p>
                        <Button variant="outline" asChild className="mt-4">
                            <Link href="/fleet/alert-preferences">
                                Alert preferences
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[700px] text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/50">
                                        <th className="w-10 p-3">
                                            <Checkbox
                                                checked={allSelected}
                                                onCheckedChange={toggleAll}
                                                aria-label="Select all"
                                            />
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Title
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Type
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Severity
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Status
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Entity
                                        </th>
                                        <th className="p-3 text-left font-medium">
                                            Triggered
                                        </th>
                                        <th className="p-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {alerts.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="p-3">
                                                <Checkbox
                                                    checked={selectedIds.has(
                                                        row.id,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleOne(row.id)
                                                    }
                                                    aria-label={`Select ${row.title}`}
                                                />
                                            </td>
                                            <td className="p-3">{row.title}</td>
                                            <td className="p-3">
                                                {row.alert_type}
                                            </td>
                                            <td className="p-3">
                                                {row.severity}
                                            </td>
                                            <td className="p-3">
                                                {row.status}
                                            </td>
                                            <td className="p-3 text-muted-foreground">
                                                {row.entity_label ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {new Date(
                                                    row.triggered_at,
                                                ).toLocaleString()}
                                            </td>
                                            <td className="p-3 text-right">
                                                {row.status === 'active' && (
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        className="mr-1"
                                                        onClick={() =>
                                                            router.post(
                                                                `/fleet/alerts/${row.id}/acknowledge`,
                                                            )
                                                        }
                                                    >
                                                        Acknowledge
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/fleet/alerts/${row.id}`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {alerts.links?.length > 1 && (
                            <div className="flex flex-wrap gap-2">
                                {alerts.links.map((link, i) => (
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
