import { router } from '@inertiajs/react';

import type { PendingQueueData } from './types';

interface Props {
    data: PendingQueueData | null;
}

const formatRs = (n: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(n);

interface TileProps {
    count: number;
    label: string;
    sub: string | null;
    href: string;
    urgent?: boolean;
}

function QueueTile({ count, label, sub, href, urgent = false }: TileProps) {
    return (
        <button
            type="button"
            className={`flex flex-1 flex-col items-start rounded-lg border p-3 text-left transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ${
                urgent
                    ? 'border-orange-200 bg-orange-50 hover:bg-orange-100'
                    : 'border-slate-200 bg-white'
            }`}
            onClick={() => router.visit(href)}
            data-pan="manager-brief-pending-queue-tile"
        >
            <span
                className={`text-2xl font-bold tabular-nums ${
                    urgent ? 'text-orange-700' : 'text-slate-800'
                }`}
            >
                {count}
            </span>
            <span className="mt-0.5 text-xs font-medium text-slate-600">
                {label}
            </span>
            {sub && (
                <span className="mt-1 text-xs text-slate-400">{sub}</span>
            )}
        </button>
    );
}

export function PendingQueue({ data }: Props) {
    if (data === null) {
        return (
            <div
                className="flex items-center justify-center rounded-lg border border-slate-200 bg-white p-4 text-2xl text-slate-400"
                data-pan="manager-brief-widget-failed"
                data-pan-meta='{"widget":"pending_queue"}'
            >
                —
            </div>
        );
    }

    const overridesSub =
        data.overrides_oldest_minutes !== null
            ? `Oldest: ${data.overrides_oldest_minutes} min ago`
            : null;

    const disputesSub = `${formatRs(data.disputes_estimated_rs)} potential recovery`;

    // Overrides → rake-loader page if it exists, else fallback
    const overridesHref = '/dashboard?section=penalty-control';
    // Disputes → penalties page (disputes section)
    const disputesHref = '/penalties';

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">
                Pending Queue
            </p>
            <div className="flex gap-3">
                <QueueTile
                    count={data.overrides_pending}
                    label="Overrides awaiting supervisor"
                    sub={overridesSub}
                    href={overridesHref}
                    urgent={data.overrides_pending > 0}
                />
                <QueueTile
                    count={data.disputes_ready}
                    label="Disputes ready to file"
                    sub={disputesSub}
                    href={disputesHref}
                />
            </div>
        </div>
    );
}
