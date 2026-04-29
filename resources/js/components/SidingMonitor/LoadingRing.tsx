import { motion, useReducedMotion } from 'framer-motion';

interface Props {
    percentage: number;
    weightMt: number;
    ccMt: number;
    sequence: number;
}

export function LoadingRing({ percentage, weightMt, ccMt, sequence }: Props) {
    const shouldReduceMotion = useReducedMotion();
    const radius = 54;
    const circumference = 2 * Math.PI * radius;
    const clampedPct = Math.min(percentage, 100);
    const offset = circumference - (clampedPct / 100) * circumference;

    const ringColor = percentage >= 100
        ? '#ef4444'
        : percentage >= 90
        ? '#f59e0b'
        : '#22c55e';

    return (
        <div className="flex flex-col items-center gap-2">
            <div className="relative h-36 w-36">
                <svg className="h-full w-full -rotate-90" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r={radius} fill="none" stroke="#1e293b" strokeWidth="8" />
                    <motion.circle
                        cx="60" cy="60" r={radius}
                        fill="none"
                        stroke={ringColor}
                        strokeWidth="8"
                        strokeLinecap="round"
                        strokeDasharray={circumference}
                        animate={{ strokeDashoffset: offset }}
                        initial={{ strokeDashoffset: circumference }}
                        transition={shouldReduceMotion ? { duration: 0 } : { duration: 0.3, ease: 'easeOut' }}
                        style={{ strokeDashoffset: offset }}
                    />
                </svg>
                <div className="absolute inset-0 flex flex-col items-center justify-center">
                    <span className={`font-mono text-2xl font-bold ${percentage >= 100 ? 'text-red-400' : percentage >= 90 ? 'text-amber-400' : 'text-green-400'}`}>
                        {percentage.toFixed(0)}%
                    </span>
                    <span className="font-mono text-xs text-slate-400">{weightMt.toFixed(1)} MT</span>
                </div>
            </div>
            <div className="text-center">
                <p className="text-xs text-slate-500">Wagon #{sequence}</p>
                <p className="font-mono text-xs text-slate-400">CC: {ccMt.toFixed(1)} MT</p>
            </div>
        </div>
    );
}
