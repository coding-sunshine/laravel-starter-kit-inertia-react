import { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

interface KpiCardProps {
    title: string;
    value: string | number;
    change?: {
        value: number;
        isPositive: boolean;
        period: string;
    };
    icon: LucideIcon;
    className?: string;
    subtitle?: string;
}

export function KpiCard({ title, value, change, icon: Icon, className, subtitle }: KpiCardProps) {
    const formatValue = (val: string | number) => {
        if (typeof val === 'number') {
            if (val >= 1000000) {
                return `${(val / 1000000).toFixed(1)}M`;
            }
            if (val >= 1000) {
                return `${(val / 1000).toFixed(1)}K`;
            }
            return val.toLocaleString();
        }
        return val;
    };

    return (
        <div className={cn("fusion-card p-6 transition-all hover:shadow-md", className)}>
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">{title}</p>
                    <div className="flex items-baseline gap-2">
                        <p className="text-2xl font-bold text-foreground">
                            {formatValue(value)}
                        </p>
                        {change && (
                            <span
                                className={cn(
                                    "text-xs font-medium px-2 py-1 rounded-full",
                                    change.isPositive
                                        ? "text-green-700 bg-green-100 dark:text-green-400 dark:bg-green-900/30"
                                        : "text-red-700 bg-red-100 dark:text-red-400 dark:bg-red-900/30"
                                )}
                            >
                                {change.isPositive ? '+' : ''}{change.value}%
                            </span>
                        )}
                    </div>
                    {subtitle && (
                        <p className="text-xs text-muted-foreground mt-1">{subtitle}</p>
                    )}
                    {change && (
                        <p className="text-xs text-muted-foreground mt-1">
                            vs {change.period}
                        </p>
                    )}
                </div>
                <div className="flex items-center justify-center w-12 h-12 bg-primary/10 rounded-lg">
                    <Icon className="w-6 h-6 text-primary" />
                </div>
            </div>
        </div>
    );
}