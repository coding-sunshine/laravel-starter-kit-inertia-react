import { motion } from 'framer-motion';

export interface TickerEvent {
    id: string;
    label: string;
    tone?: 'add' | 'subtract' | 'shortTotal' | 'info';
}

interface Props {
    events: TickerEvent[];
    speedSeconds?: number;
}

const TONE: Record<NonNullable<TickerEvent['tone']>, string> = {
    add: 'text-emerald-700',
    subtract: 'text-rose-700',
    shortTotal: 'text-sky-700 font-semibold',
    info: 'text-slate-700',
};

export function EventTickerMarquee({ events, speedSeconds = 60 }: Props) {
    if (events.length === 0) {
        return (
            <div className="overflow-hidden rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-400">
                Waiting for Loadrite events…
            </div>
        );
    }

    const lane = [...events, ...events];

    return (
        <div className="relative overflow-hidden rounded-md border border-slate-200 bg-white py-1">
            <motion.div
                className="flex whitespace-nowrap"
                initial={{ x: 0 }}
                animate={{ x: '-50%' }}
                transition={{
                    repeat: Infinity,
                    ease: 'linear',
                    duration: speedSeconds,
                }}
            >
                {lane.map((e, i) => (
                    <span
                        key={`${e.id}-${i}`}
                        className={`mx-4 inline-flex items-center text-xs ${TONE[e.tone ?? 'info']}`}
                    >
                        <span className="mr-2 text-slate-400">▸</span>
                        {e.label}
                    </span>
                ))}
            </motion.div>
        </div>
    );
}
