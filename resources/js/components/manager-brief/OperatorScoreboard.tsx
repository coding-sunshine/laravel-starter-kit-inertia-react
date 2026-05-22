import { router } from '@inertiajs/react';

import type { Operator, OperatorScoreboardData } from './types';

interface Props {
    data: OperatorScoreboardData | null;
}

const formatRs = (n: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(n);

function OperatorRow({ operator }: { operator: Operator }) {
    // The dashboard uses section=loader-overload with operator filter
    const handleClick = () => {
        router.visit(
            '/dashboard?section=loader-overload&operator=' +
                encodeURIComponent(operator.name),
        );
    };

    return (
        <tr
            className="cursor-pointer border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50"
            onClick={handleClick}
            data-pan="manager-brief-operator-row"
        >
            <td className="py-1.5 pr-2 text-sm font-medium text-slate-800">
                {operator.name}
            </td>
            <td className="py-1.5 pr-2 text-right text-xs tabular-nums text-slate-500">
                {operator.wagons}
            </td>
            <td className="py-1.5 pr-2 text-right text-xs tabular-nums text-slate-700">
                {operator.accuracy_pct.toFixed(1)}%
            </td>
            <td className="py-1.5 text-right text-xs tabular-nums text-red-600">
                {formatRs(operator.rs_caused)}
            </td>
        </tr>
    );
}

function OperatorTable({
    operators,
    label,
}: {
    operators: Operator[];
    label: string;
}) {
    if (operators.length === 0) {
        return (
            <div>
                <p className="mb-1 text-xs font-semibold text-slate-500">{label}</p>
                <p className="text-xs text-slate-400">No data this week.</p>
            </div>
        );
    }

    return (
        <div>
            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>
            <table className="w-full">
                <thead>
                    <tr className="border-b border-slate-100 text-right text-xs text-slate-400">
                        <th className="pb-1 pr-2 text-left font-medium">Name</th>
                        <th className="pb-1 pr-2 font-medium">Wagons</th>
                        <th className="pb-1 pr-2 font-medium">Accuracy</th>
                        <th className="pb-1 font-medium">₹ Caused</th>
                    </tr>
                </thead>
                <tbody>
                    {operators.map((op) => (
                        <OperatorRow key={op.name} operator={op} />
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function OperatorScoreboard({ data }: Props) {
    if (data === null) {
        return (
            <div
                className="flex items-center justify-center rounded-lg border border-slate-200 bg-white p-4 text-2xl text-slate-400"
                data-pan="manager-brief-widget-failed"
                data-pan-meta='{"widget":"operator_scoreboard"}'
            >
                —
            </div>
        );
    }

    // top sorted desc by accuracy, bottom sorted asc by accuracy
    const sortedTop = [...data.top].sort(
        (a, b) => b.accuracy_pct - a.accuracy_pct,
    );
    const sortedBottom = [...data.bottom].sort(
        (a, b) => a.accuracy_pct - b.accuracy_pct,
    );

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">
                Operator Scoreboard
            </p>
            <div className="space-y-4">
                <OperatorTable operators={sortedTop} label="Best this week" />
                <OperatorTable operators={sortedBottom} label="Needs coaching" />
            </div>
        </div>
    );
}
