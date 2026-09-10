import { AnimatePresence, motion } from 'framer-motion';
import { AlertTriangle, X } from 'lucide-react';
import { useEffect } from 'react';

export interface ToastItem {
    id: string;
    title: string;
    body?: string;
    severity?: 'warning' | 'critical' | 'info';
}

interface Props {
    toasts: ToastItem[];
    onDismiss: (id: string) => void;
    autoDismissMs?: number;
}

const SEVERITY: Record<NonNullable<ToastItem['severity']>, string> = {
    critical: 'border-rose-300 bg-rose-50 text-rose-900',
    warning: 'border-amber-300 bg-amber-50 text-amber-900',
    info: 'border-sky-300 bg-sky-50 text-sky-900',
};

export function AlertToast({ toasts, onDismiss, autoDismissMs = 6000 }: Props) {
    useEffect(() => {
        if (toasts.length === 0) return;
        const timers = toasts.map((t) =>
            window.setTimeout(() => onDismiss(t.id), autoDismissMs),
        );
        return () => timers.forEach((id) => window.clearTimeout(id));
    }, [toasts, autoDismissMs, onDismiss]);

    return (
        <div
            aria-live="polite"
            aria-atomic="true"
            className="pointer-events-none fixed right-4 top-20 z-50 flex w-80 flex-col gap-2"
        >
            <AnimatePresence>
                {toasts.map((t) => (
                    <motion.div
                        key={t.id}
                        layout
                        initial={{ opacity: 0, x: 40 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: 60 }}
                        transition={{ duration: 0.25 }}
                        className={`pointer-events-auto rounded-lg border p-3 shadow-md ${SEVERITY[t.severity ?? 'warning']}`}
                    >
                        <div className="flex items-start gap-2">
                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden />
                            <div className="min-w-0 flex-1">
                                <div className="text-sm font-semibold">
                                    {t.title}
                                </div>
                                {t.body && (
                                    <div className="mt-0.5 text-xs opacity-90">
                                        {t.body}
                                    </div>
                                )}
                            </div>
                            <button
                                type="button"
                                onClick={() => onDismiss(t.id)}
                                className="rounded-md p-1 hover:bg-white/50"
                                aria-label="Dismiss"
                            >
                                <X className="h-3.5 w-3.5" aria-hidden />
                            </button>
                        </div>
                    </motion.div>
                ))}
            </AnimatePresence>
        </div>
    );
}
