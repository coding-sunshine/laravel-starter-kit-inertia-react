import { formatDistanceToNow } from 'date-fns';

import type { AiStatus } from './types';

interface Props {
    generatedAt: string;
    aiStatus: AiStatus;
}

export function StalenessBanner({ generatedAt, aiStatus }: Props) {
    if (aiStatus === 'pending') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                <span className="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500" />
                Generating insights…
            </span>
        );
    }

    if (aiStatus === 'failed') {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
                AI insights unavailable — showing live data only
            </span>
        );
    }

    // aiStatus === 'ok'
    const generatedDate = new Date(generatedAt);
    const ageMs = Date.now() - generatedDate.getTime();
    const ageMinutes = ageMs / 60_000;

    if (ageMinutes < 60) {
        // Fresh — render nothing
        return null;
    }

    const relativeTime = formatDistanceToNow(generatedDate, { addSuffix: true });

    if (ageMinutes < 120) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                Updated {relativeTime}
            </span>
        );
    }

    return (
        <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">
            Insights &gt; 2h old — updated {relativeTime}
        </span>
    );
}
