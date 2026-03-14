/**
 * C1 PipelineFunnel component — renders a reservation/sales pipeline as a funnel chart.
 */

import type { PipelineFunnelProps } from '../c1-types';

function formatCurrency(amount?: number, currency = 'AUD'): string {
    if (amount === undefined || amount === 0) return '';
    return new Intl.NumberFormat('en-AU', { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount);
}

const STAGE_COLORS = [
    'bg-blue-500',
    'bg-blue-400',
    'bg-indigo-400',
    'bg-purple-400',
    'bg-violet-400',
    'bg-green-500',
];

export function PipelineFunnel({ title, stages, total_count, total_value, currency = 'AUD' }: PipelineFunnelProps) {
    const maxCount = Math.max(...stages.map((s) => s.count), 1);

    return (
        <div className="rounded-lg border border-border bg-card p-4 shadow-sm">
            <h3 className="mb-3 font-semibold text-sm">{title}</h3>

            <div className="space-y-2">
                {stages.map((stage, i) => {
                    const widthPct = Math.max((stage.count / maxCount) * 100, 5);
                    const color = stage.color ? '' : STAGE_COLORS[i % STAGE_COLORS.length];

                    return (
                        <div key={stage.name} className="flex items-center gap-3">
                            {/* Label */}
                            <div className="w-28 flex-shrink-0 text-right text-xs text-muted-foreground">
                                {stage.name}
                            </div>

                            {/* Bar */}
                            <div className="relative flex-1 rounded bg-muted" style={{ height: 24 }}>
                                <div
                                    className={`absolute inset-y-0 left-0 rounded ${color} transition-all`}
                                    style={{ width: `${widthPct}%`, backgroundColor: stage.color ?? undefined }}
                                />
                                <span className="absolute inset-0 flex items-center justify-start pl-2 text-xs font-medium text-white">
                                    {stage.count}
                                </span>
                            </div>

                            {/* Value */}
                            {stage.value !== undefined && stage.value > 0 && (
                                <div className="w-24 flex-shrink-0 text-right text-xs text-muted-foreground">
                                    {formatCurrency(stage.value, currency)}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Totals row */}
            <div className="mt-3 flex items-center justify-between border-t border-border pt-2 text-xs text-muted-foreground">
                <span>{total_count} total deals</span>
                {total_value !== undefined && total_value > 0 && (
                    <span className="font-medium">{formatCurrency(total_value, currency)}</span>
                )}
            </div>
        </div>
    );
}
