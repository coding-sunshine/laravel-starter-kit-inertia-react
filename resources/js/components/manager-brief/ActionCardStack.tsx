import { router } from '@inertiajs/react';

import type { ActionCard, AiStatus, Severity } from './types';

interface Props {
    actions: ActionCard[];
    aiStatus: AiStatus;
}

const SEVERITY_CLASSES: Record<Severity, { card: string; bar: string; badge: string }> = {
    critical: {
        card: 'bg-red-50 border-red-300 text-red-900 hover:bg-red-100',
        bar: 'bg-red-400',
        badge: 'bg-red-100 text-red-800',
    },
    high: {
        card: 'bg-orange-50 border-orange-300 text-orange-900 hover:bg-orange-100',
        bar: 'bg-orange-400',
        badge: 'bg-orange-100 text-orange-800',
    },
    medium: {
        card: 'bg-yellow-50 border-yellow-300 text-yellow-900 hover:bg-yellow-100',
        bar: 'bg-yellow-400',
        badge: 'bg-yellow-100 text-yellow-800',
    },
    low: {
        card: 'bg-green-50 border-green-300 text-green-900 hover:bg-green-100',
        bar: 'bg-green-400',
        badge: 'bg-green-100 text-green-800',
    },
};

const formatRs = (rs: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(rs);

export function ActionCardStack({ actions, aiStatus }: Props) {
    if (aiStatus === 'failed' && actions.length === 0) {
        return (
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-sm text-slate-500">
                AI insights unavailable — live widget data is still shown below.
            </div>
        );
    }

    if (aiStatus === 'pending' && actions.length === 0) {
        return (
            <div className="space-y-3">
                {[1, 2, 3].map((i) => (
                    <div
                        key={i}
                        className="h-20 animate-pulse rounded-lg border border-slate-200 bg-slate-100"
                    />
                ))}
                <p className="text-center text-sm text-slate-400">
                    Generating insights…
                </p>
            </div>
        );
    }

    if (actions.length === 0) {
        return (
            <div className="rounded-lg border border-green-200 bg-green-50 p-6 text-center text-sm text-green-700">
                No actions required — everything looks good.
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {actions.map((card, idx) => {
                const cls = SEVERITY_CLASSES[card.severity];
                return (
                    <button
                        key={idx}
                        type="button"
                        className={`relative flex w-full overflow-hidden rounded-lg border text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ${cls.card}`}
                        onClick={() => router.visit(card.deep_link)}
                        data-pan="manager-brief-action-card"
                        data-pan-meta={JSON.stringify({ severity: card.severity })}
                    >
                        {/* Left severity bar */}
                        <div className={`w-1.5 flex-shrink-0 self-stretch ${cls.bar}`} />

                        <div className="flex flex-1 flex-col gap-1 px-4 py-3">
                            <div className="flex flex-wrap items-center gap-2">
                                <span
                                    className={`rounded px-2 py-0.5 text-xs font-semibold uppercase tracking-wide ${cls.badge}`}
                                >
                                    {card.severity}
                                </span>
                                <span className="text-sm font-semibold">
                                    {card.title}
                                </span>
                                {card.deadline && (
                                    <span className="ml-auto text-xs opacity-70">
                                        Due {card.deadline}
                                    </span>
                                )}
                            </div>
                            <p className="text-sm opacity-80">{card.why}</p>
                            <p className="text-xs font-medium opacity-70">
                                {formatRs(card.rs_at_stake)} at stake
                            </p>
                        </div>
                    </button>
                );
            })}
        </div>
    );
}
