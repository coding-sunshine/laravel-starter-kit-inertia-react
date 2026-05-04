import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { AlertCircle, AlertTriangle, CheckCircle2, Info } from 'lucide-react';
import type { ComponentType } from 'react';

import type { AlertRow } from '@/components/control-room/types';
import { cn } from '@/lib/utils';

interface AlertsFeedProps {
    alerts: AlertRow[];
    maxVisible?: number;
    onViewAll?: () => void;
}

type IconComponent = ComponentType<{ className?: string }>;

interface SeverityStyle {
    Icon: IconComponent;
    iconClass: string;
    bgClass: string;
}

function getSeverityStyle(severity: string): SeverityStyle {
    const key = (severity ?? '').toLowerCase();

    switch (key) {
        case 'critical':
            return {
                Icon: AlertTriangle,
                iconClass: 'text-red-600 dark:text-red-400',
                bgClass: 'bg-red-100 dark:bg-red-950/40',
            };
        case 'warning':
        case 'warn':
            return {
                Icon: AlertCircle,
                iconClass: 'text-amber-600 dark:text-amber-400',
                bgClass: 'bg-amber-100 dark:bg-amber-950/40',
            };
        case 'success':
        case 'resolved':
            return {
                Icon: CheckCircle2,
                iconClass: 'text-emerald-600 dark:text-emerald-400',
                bgClass: 'bg-emerald-100 dark:bg-emerald-950/40',
            };
        case 'info':
        default:
            return {
                Icon: Info,
                iconClass: 'text-sky-600 dark:text-sky-400',
                bgClass: 'bg-sky-100 dark:bg-sky-950/40',
            };
    }
}

function formatTime(value: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

export function AlertsFeed({
    alerts,
    maxVisible = 6,
    onViewAll,
}: AlertsFeedProps) {
    const prefersReducedMotion = useReducedMotion();
    const visible = alerts.slice(0, maxVisible);

    const initial = prefersReducedMotion
        ? { opacity: 1, x: 0 }
        : { x: 40, opacity: 0 };
    const animate = { x: 0, opacity: 1 };
    const exit = prefersReducedMotion
        ? { opacity: 0 }
        : { x: 40, opacity: 0, height: 0 };

    return (
        <div className="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div className="flex items-center justify-between border-b border-border px-4 py-3">
                <h3 className="text-sm font-semibold tracking-tight">Alerts</h3>
                {onViewAll ? (
                    <button
                        type="button"
                        onClick={onViewAll}
                        className="rounded text-xs font-medium text-primary hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        View All
                    </button>
                ) : null}
            </div>

            <div className="px-2 py-2">
                {visible.length === 0 ? (
                    <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                        No active alerts.
                    </p>
                ) : (
                    <ul className="flex flex-col">
                        <AnimatePresence initial={false}>
                            {visible.map((alert) => {
                                const { Icon, iconClass, bgClass } =
                                    getSeverityStyle(alert.severity);
                                const timeLabel = formatTime(alert.created_at);

                                return (
                                    <motion.li
                                        key={alert.id}
                                        layout={!prefersReducedMotion}
                                        initial={initial}
                                        animate={animate}
                                        exit={exit}
                                        transition={{
                                            duration: prefersReducedMotion
                                                ? 0
                                                : 0.25,
                                            ease: 'easeOut',
                                        }}
                                        className="overflow-hidden"
                                    >
                                        <div className="flex items-start gap-3 rounded-md px-3 py-2.5 hover:bg-muted/50">
                                            <div
                                                className={cn(
                                                    'mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full',
                                                    bgClass,
                                                )}
                                            >
                                                <Icon
                                                    className={cn(
                                                        'h-4 w-4',
                                                        iconClass,
                                                    )}
                                                />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                {alert.title ? (
                                                    <p className="text-sm leading-tight font-semibold text-foreground">
                                                        {alert.title}
                                                    </p>
                                                ) : null}
                                                {alert.body ? (
                                                    <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                                                        {alert.body}
                                                    </p>
                                                ) : null}
                                            </div>
                                            {timeLabel ? (
                                                <span className="shrink-0 text-xs text-muted-foreground tabular-nums">
                                                    {timeLabel}
                                                </span>
                                            ) : null}
                                        </div>
                                    </motion.li>
                                );
                            })}
                        </AnimatePresence>
                    </ul>
                )}
            </div>
        </div>
    );
}

export default AlertsFeed;
