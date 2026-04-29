import { motion, useReducedMotion } from 'framer-motion';
import { useEffect, useRef, useState } from 'react';

interface Props {
    placementTime: string | null;
    freeMinutes: number;
}

function formatHMS(totalSeconds: number): string {
    const abs = Math.abs(totalSeconds);
    const h = Math.floor(abs / 3600).toString().padStart(2, '0');
    const m = Math.floor((abs % 3600) / 60).toString().padStart(2, '0');
    const s = Math.floor(abs % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
}

export function CountdownTimer({ placementTime, freeMinutes }: Props) {
    const [elapsed, setElapsed] = useState(0);
    const rafRef = useRef<number>(0);
    const shouldReduceMotion = useReducedMotion();

    useEffect(() => {
        if (!placementTime) return;

        const placed = new Date(placementTime).getTime();

        const tick = () => {
            const elapsedSeconds = Math.floor((Date.now() - placed) / 1000);
            setElapsed(elapsedSeconds);
            rafRef.current = requestAnimationFrame(tick);
        };

        rafRef.current = requestAnimationFrame(tick);
        return () => cancelAnimationFrame(rafRef.current);
    }, [placementTime]);

    const freeSeconds = freeMinutes * 60;
    const remainingSeconds = freeSeconds - elapsed;
    const isOverrun = remainingSeconds < 0;

    return (
        <div className="flex items-center gap-6 rounded-xl border border-slate-800 bg-slate-900/60 px-6 py-4">
            <Stat label="Elapsed" value={formatHMS(elapsed)} mono />
            <div className="h-8 w-px bg-slate-700" />
            <motion.div
                animate={shouldReduceMotion ? {} : { scale: isOverrun ? [1, 1.05, 1] : 1 }}
                transition={{ duration: 0.3, ease: 'easeOut' }}
            >
                <Stat
                    label={isOverrun ? 'Overrun' : 'Remaining'}
                    value={(isOverrun ? '+' : '') + formatHMS(Math.abs(remainingSeconds))}
                    mono
                    highlight={isOverrun ? 'red' : 'green'}
                />
            </motion.div>
            <div className="h-8 w-px bg-slate-700" />
            <Stat label="Free window" value={formatHMS(freeSeconds)} mono />
        </div>
    );
}

function Stat({ label, value, mono, highlight }: { label: string; value: string; mono?: boolean; highlight?: 'red' | 'green' }) {
    const color = highlight === 'red' ? 'text-red-400' : highlight === 'green' ? 'text-green-400' : 'text-slate-200';
    return (
        <div className="text-center">
            <p className="mb-0.5 text-xs text-slate-500">{label}</p>
            <p className={`${mono ? 'font-mono' : ''} text-lg font-semibold ${color}`}>{value}</p>
        </div>
    );
}
