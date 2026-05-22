import { animate, motion, useMotionValue, useTransform } from 'framer-motion';
import { useEffect, useRef } from 'react';

import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';

import type { LiveExposureData } from './types';

interface Props {
    data: LiveExposureData | null;
}

const formatRs = (n: number) =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(n);

function AnimatedRs({ value }: { value: number }) {
    const motionValue = useMotionValue(value);
    const display = useTransform(motionValue, (n) =>
        new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: 'INR',
            maximumFractionDigits: 0,
        }).format(n),
    );
    const previous = useRef<number>(value);

    useEffect(() => {
        if (previous.current === value) return;
        const controls = animate(motionValue, value, {
            duration: 0.8,
            ease: 'easeOut',
        });
        previous.current = value;
        return () => controls.stop();
    }, [value, motionValue]);

    return <motion.span>{display}</motion.span>;
}

export function LiveExposureTicker({ data }: Props) {
    if (data === null) {
        return (
            <div
                className="flex items-center justify-center rounded-lg border border-slate-200 bg-white p-4 text-2xl text-slate-400"
                data-pan="manager-brief-widget-failed"
                data-pan-meta='{"widget":"live_exposure"}'
            >
                —
            </div>
        );
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">
                Live Penalty Exposure
            </p>
            <Popover>
                <PopoverTrigger asChild>
                    <button
                        type="button"
                        className="cursor-pointer text-2xl font-bold tabular-nums text-red-600 hover:underline focus:outline-none"
                        data-pan="manager-brief-live-exposure-expand"
                    >
                        <AnimatedRs value={data.total_rs} />
                    </button>
                </PopoverTrigger>
                <PopoverContent className="w-80 p-0" align="start">
                    <div className="px-4 py-3">
                        <p className="mb-2 text-sm font-semibold text-slate-700">
                            Breakdown by Rake
                        </p>
                        {data.breakdown.length === 0 ? (
                            <p className="text-xs text-slate-400">
                                No breakdown available.
                            </p>
                        ) : (
                            <table className="w-full text-xs">
                                <thead>
                                    <tr className="border-b border-slate-100 text-left text-slate-500">
                                        <th className="pb-1 pr-2 font-medium">Rake</th>
                                        <th className="pb-1 pr-2 font-medium">Overload</th>
                                        <th className="pb-1 font-medium">₹ Risk</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.breakdown.map((row) => (
                                        <tr
                                            key={row.rake_id}
                                            className="border-b border-slate-50 last:border-0"
                                        >
                                            <td className="py-1 pr-2 font-medium">
                                                {row.rake_number}
                                            </td>
                                            <td className="py-1 pr-2 text-slate-600">
                                                {row.overload_mt.toFixed(2)} MT
                                            </td>
                                            <td className="py-1 text-red-600">
                                                {formatRs(row.rs)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </PopoverContent>
            </Popover>
            <p className="mt-1 text-xs text-slate-400">
                Across {data.breakdown.length} rake
                {data.breakdown.length !== 1 ? 's' : ''}
            </p>
        </div>
    );
}
