import type { WorkflowSteps } from '@/components/rake-workflow-progress';
import type { ComponentType, ReactNode } from 'react';

export const DEFAULT_LIVE_RAKE_WORKFLOW_STEPS: WorkflowSteps = {
    txr_done: false,
    wagon_loading_done: false,
    guard_done: false,
    weighment_done: false,
    rr_done: false,
};

export function formatRakeSequenceBySiding(
    rakeNumber: string,
    sidingName: string,
): string {
    const normalized = rakeNumber.trim();
    if (normalized === '') {
        return normalized;
    }

    const siding = sidingName.toLowerCase();
    let prefix = '';
    if (siding.includes('pakur')) {
        prefix = 'P';
    } else if (siding.includes('kurwa')) {
        prefix = 'K';
    } else if (siding.includes('dumka')) {
        prefix = 'D';
    }

    if (prefix === '') {
        return normalized;
    }

    return normalized.startsWith(`${prefix}-`)
        ? normalized
        : `${prefix}-${normalized}`;
}

export function formatCurrency(n: number): string {
    if (n >= 100000) return `₹${(n / 100000).toFixed(1)}L`;
    if (n >= 1000) return `₹${(n / 1000).toFixed(1)}K`;
    return `₹${n.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

export function formatWeight(n: number): string {
    if (n >= 1000) return `${(n / 1000).toFixed(1)}K MT`;
    return `${n.toLocaleString()} MT`;
}

export function SectionHeader({
    icon: Icon,
    title,
    subtitle,
    action,
    titleClassName,
}: {
    icon: ComponentType<{ className?: string }>;
    title: string;
    subtitle?: string;
    action?: ReactNode;
    titleClassName?: string;
}) {
    return (
        <div className="flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
                <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10">
                    <Icon className="size-4.5 text-primary" />
                </div>
                <div>
                    <h3
                        className={`text-base font-semibold ${titleClassName ?? ''}`.trim()}
                    >
                        {title}
                    </h3>
                    {subtitle && (
                        <p className="text-xs text-gray-600">{subtitle}</p>
                    )}
                </div>
            </div>
            {action}
        </div>
    );
}

export const SIDING_ACCENT: Record<string, string> = {
    Dumka: '#3B82F6',
    Kurwa: '#10B981',
    Pakur: '#F59E0B',
};
