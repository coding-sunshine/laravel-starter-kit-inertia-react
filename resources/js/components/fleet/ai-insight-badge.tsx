'use client';

import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { BrainCircuit } from 'lucide-react';

interface AiInsightBadgeProps {
    text: string;
    priority: 'high' | 'medium' | 'low';
    analysisType?: string;
    href?: string;
    className?: string;
}

const PRIORITY_STYLES: Record<string, string> = {
    high: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
    medium: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300',
    low: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
};

const PRIORITY_ICON_STYLES: Record<string, string> = {
    high: 'text-red-500 dark:text-red-400',
    medium: 'text-amber-500 dark:text-amber-400',
    low: 'text-blue-500 dark:text-blue-400',
};

export function AiInsightBadge({ text, priority, analysisType, href, className }: AiInsightBadgeProps) {
    const styles = PRIORITY_STYLES[priority] ?? PRIORITY_STYLES.low;
    const iconStyles = PRIORITY_ICON_STYLES[priority] ?? PRIORITY_ICON_STYLES.low;

    const content = (
        <span
            className={cn(
                'inline-flex max-w-xs items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium leading-tight',
                styles,
                href && 'cursor-pointer transition-opacity hover:opacity-80',
                className,
            )}
        >
            <BrainCircuit className={cn('size-3.5 shrink-0', iconStyles)} />
            {analysisType && (
                <span className="shrink-0 font-semibold">{analysisType}:</span>
            )}
            <span className="truncate">{text}</span>
        </span>
    );

    if (href) {
        return <Link href={href}>{content}</Link>;
    }

    return content;
}
