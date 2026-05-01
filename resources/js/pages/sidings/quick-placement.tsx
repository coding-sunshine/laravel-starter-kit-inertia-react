import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Head, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Circle, Clock, MapPin, Train } from 'lucide-react';
import { useMemo, useState } from 'react';

type Rake = {
    id: number;
    rake_number: string;
    placement_time: string | null;
    loading_end_time: string | null;
};

type Props = {
    siding: { id: number; name: string };
    rakes: Rake[];
};

type FlashBag = {
    success?: string;
    error?: string;
};

function formatRelative(iso: string): string {
    const then = new Date(iso).getTime();
    const now = Date.now();
    const minutes = Math.round((now - then) / 60000);
    if (Number.isNaN(minutes)) return '';
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.round(hours / 24);
    return `${days}d ago`;
}

function formatAbsolute(iso: string): string {
    return new Date(iso).toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

type RakeStatus = 'idle' | 'placed' | 'released';

function statusOf(rake: Rake): RakeStatus {
    if (rake.loading_end_time) return 'released';
    if (rake.placement_time) return 'placed';
    return 'idle';
}

export default function QuickPlacement({ siding, rakes }: Props) {
    const { props } = usePage<{ flash: FlashBag }>();
    const flash = props.flash ?? {};
    const [pending, setPending] = useState<{ id: number; event: 'placed' | 'released' } | null>(null);

    const counts = useMemo(() => {
        let idle = 0;
        let placed = 0;
        let released = 0;
        for (const r of rakes) {
            const s = statusOf(r);
            if (s === 'idle') idle++;
            else if (s === 'placed') placed++;
            else released++;
        }
        return { idle, placed, released, total: rakes.length };
    }, [rakes]);

    const submit = (rakeId: number, event: 'placed' | 'released') => {
        setPending({ id: rakeId, event });
        router.post(
            `/sidings/${siding.id}/quick-placement`,
            { rake_id: rakeId, event },
            {
                preserveScroll: true,
                onFinish: () => setPending(null),
            },
        );
    };

    return (
        <>
            <Head title={`Quick placement — ${siding.name}`} />
            <div className="min-h-screen bg-background">
                <div className="mx-auto max-w-md px-4 py-6 sm:max-w-2xl sm:px-6 sm:py-8">
                    {/* Header */}
                    <header className="mb-6">
                        <div className="mb-2 flex items-center gap-2 text-xs uppercase tracking-wide text-muted-foreground">
                            <MapPin className="h-3.5 w-3.5" aria-hidden="true" />
                            <span>Siding</span>
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">{siding.name}</h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Tap once when a rake is placed, again when released. The server stamps the time.
                        </p>
                        {counts.total > 0 && (
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Badge variant="outline" className="font-normal">
                                    {counts.total} rake{counts.total === 1 ? '' : 's'}
                                </Badge>
                                {counts.idle > 0 && (
                                    <Badge variant="outline" className="font-normal text-muted-foreground">
                                        {counts.idle} not placed
                                    </Badge>
                                )}
                                {counts.placed > 0 && (
                                    <Badge variant="outline" className="border-amber-500/40 font-normal text-amber-600 dark:text-amber-400">
                                        {counts.placed} loading
                                    </Badge>
                                )}
                                {counts.released > 0 && (
                                    <Badge variant="outline" className="border-emerald-500/40 font-normal text-emerald-600 dark:text-emerald-400">
                                        {counts.released} released
                                    </Badge>
                                )}
                            </div>
                        )}
                    </header>

                    {/* Flash messages */}
                    {flash.success && (
                        <div
                            role="status"
                            className="mb-4 rounded-md border border-emerald-500/30 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200"
                        >
                            {flash.success}
                        </div>
                    )}
                    {flash.error && (
                        <div
                            role="alert"
                            className="mb-4 rounded-md border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive"
                        >
                            {flash.error}
                        </div>
                    )}

                    {/* Empty state */}
                    {rakes.length === 0 && (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center gap-3 py-12 text-center">
                                <Train className="h-10 w-10 text-muted-foreground/50" aria-hidden="true" />
                                <p className="text-sm font-medium">No active rakes</p>
                                <p className="text-xs text-muted-foreground">
                                    Once a rake is allocated to this siding it will appear here.
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    {/* Rake list */}
                    {rakes.length > 0 && (
                        <ul className="space-y-3">
                            {rakes.map((r) => {
                                const status = statusOf(r);
                                const isPending = pending?.id === r.id;
                                const placedDisabled = isPending || status !== 'idle';
                                const releasedDisabled = isPending || status !== 'placed';

                                return (
                                    <li key={r.id}>
                                        <Card className="transition-colors hover:border-primary/40">
                                            <CardContent className="p-4">
                                                <div className="flex flex-wrap items-start justify-between gap-2">
                                                    <div className="flex items-start gap-3">
                                                        <Train className="mt-0.5 h-5 w-5 text-muted-foreground" aria-hidden="true" />
                                                        <div>
                                                            <div className="font-medium leading-tight">{r.rake_number}</div>
                                                            <RakeTimeline rake={r} />
                                                        </div>
                                                    </div>
                                                    <RakeStatusBadge status={status} />
                                                </div>

                                                <div className="mt-4 flex gap-2">
                                                    <Button
                                                        type="button"
                                                        size="lg"
                                                        className="flex-1"
                                                        disabled={placedDisabled}
                                                        onClick={() => submit(r.id, 'placed')}
                                                        data-pan="sidings-quick-placement-placed"
                                                        aria-label={`Mark rake ${r.rake_number} as placed`}
                                                    >
                                                        <CheckCircle2 className="h-4 w-4" aria-hidden="true" />
                                                        {isPending && pending?.event === 'placed' ? 'Saving…' : 'Placed'}
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="lg"
                                                        className="flex-1"
                                                        disabled={releasedDisabled}
                                                        onClick={() => submit(r.id, 'released')}
                                                        data-pan="sidings-quick-placement-released"
                                                        aria-label={`Mark rake ${r.rake_number} as released`}
                                                    >
                                                        <Circle className="h-4 w-4" aria-hidden="true" />
                                                        {isPending && pending?.event === 'released' ? 'Saving…' : 'Released'}
                                                    </Button>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </div>
            </div>
        </>
    );
}

function RakeTimeline({ rake }: { rake: Rake }) {
    if (!rake.placement_time && !rake.loading_end_time) {
        return <p className="mt-1 text-xs text-muted-foreground">Not placed yet</p>;
    }
    return (
        <dl className="mt-1 space-y-0.5 text-xs text-muted-foreground">
            {rake.placement_time && (
                <div className="flex items-center gap-1.5">
                    <Clock className="h-3 w-3" aria-hidden="true" />
                    <dt className="sr-only">Placed</dt>
                    <dd>
                        <span className="font-medium text-foreground/80">Placed</span>
                        <span className="mx-1">·</span>
                        <span title={formatAbsolute(rake.placement_time)}>{formatRelative(rake.placement_time)}</span>
                    </dd>
                </div>
            )}
            {rake.loading_end_time && (
                <div className="flex items-center gap-1.5">
                    <Clock className="h-3 w-3" aria-hidden="true" />
                    <dt className="sr-only">Released</dt>
                    <dd>
                        <span className="font-medium text-foreground/80">Released</span>
                        <span className="mx-1">·</span>
                        <span title={formatAbsolute(rake.loading_end_time)}>{formatRelative(rake.loading_end_time)}</span>
                    </dd>
                </div>
            )}
        </dl>
    );
}

function RakeStatusBadge({ status }: { status: RakeStatus }) {
    if (status === 'idle') {
        return (
            <Badge variant="outline" className="font-normal text-muted-foreground">
                Not placed
            </Badge>
        );
    }
    if (status === 'placed') {
        return (
            <Badge variant="outline" className="border-amber-500/40 font-normal text-amber-700 dark:text-amber-300">
                Loading
            </Badge>
        );
    }
    return (
        <Badge variant="outline" className="border-emerald-500/40 font-normal text-emerald-700 dark:text-emerald-300">
            Released
        </Badge>
    );
}
