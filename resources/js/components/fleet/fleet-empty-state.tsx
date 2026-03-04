import { EmptyState } from '@/components/empty-state';
import { BotMessageSquare, CheckCircle2 } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

interface FleetEmptyStateProps {
    icon: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
    className?: string;
    aiSuggestion?: string;
    features?: string[];
}

/** Re-exports shared EmptyState for Fleet pages with optional AI suggestion and feature list. */
export function FleetEmptyState({
    aiSuggestion,
    features,
    ...props
}: FleetEmptyStateProps) {
    return (
        <div>
            <EmptyState {...props} />

            {(aiSuggestion || features) && (
                <div className="mt-4 flex flex-col items-center gap-4 text-center">
                    {aiSuggestion && (
                        <div className="inline-flex items-center gap-2 rounded-lg border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm text-violet-700 dark:border-violet-800 dark:bg-violet-950 dark:text-violet-300">
                            <BotMessageSquare className="size-4 shrink-0" />
                            <span>
                                Try: Ask the Fleet Assistant:{' '}
                                <span className="font-medium">
                                    &lsquo;{aiSuggestion}&rsquo;
                                </span>
                            </span>
                        </div>
                    )}

                    {features && features.length > 0 && (
                        <div className="max-w-md">
                            <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                What you can do here
                            </p>
                            <ul className="space-y-1.5 text-sm text-muted-foreground">
                                {features.map((feature) => (
                                    <li
                                        key={feature}
                                        className="flex items-center justify-center gap-1.5"
                                    >
                                        <CheckCircle2 className="size-3.5 shrink-0 text-emerald-500" />
                                        {feature}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
