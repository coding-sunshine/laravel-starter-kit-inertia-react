import { AnimatePresence, motion } from 'framer-motion';
import type { AlertItem } from '@/stores/useSidingStore';

interface Props {
    alerts: AlertItem[];
}

export function AlertsFeed({ alerts }: Props) {
    if (alerts.length === 0) {
        return (
            <div className="rounded-xl border border-slate-800 bg-slate-900/40 p-4 text-center text-sm text-slate-600">
                No alerts
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <AnimatePresence initial={false}>
                {alerts.map((alert) => (
                    <motion.div
                        key={alert.id}
                        initial={{ opacity: 0, x: 60 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: 60, height: 0, marginBottom: 0 }}
                        transition={{ duration: 0.2, ease: 'easeOut' }}
                        className={`flex items-center justify-between rounded-lg border px-4 py-2.5 text-sm ${
                            alert.level === 'critical'
                                ? 'border-red-800 bg-red-950/60 text-red-300'
                                : 'border-amber-800 bg-amber-950/60 text-amber-300'
                        }`}
                    >
                        <div className="flex items-center gap-2">
                            <span className="font-semibold">
                                {alert.level === 'critical' ? '🔴' : '⚠️'} Wagon {alert.wagonNumber}
                            </span>
                            <span className="text-xs opacity-75">
                                {alert.percentage.toFixed(1)}% CC — {alert.weightMt.toFixed(1)} MT
                            </span>
                        </div>
                        <span className="font-mono text-xs opacity-50">
                            {new Date(alert.receivedAt).toLocaleTimeString()}
                        </span>
                    </motion.div>
                ))}
            </AnimatePresence>
        </div>
    );
}
