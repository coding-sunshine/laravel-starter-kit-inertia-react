/**
 * C1 ContactCard component — renders a CRM contact summary with stage dot, lead score badge,
 * last-contact recency, and quick-action buttons.
 */

import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { ContactCardProps, C1Action } from '../c1-types';

function leadScoreVariant(score: number): string {
    if (score > 60) return 'bg-green-100 text-green-800';
    if (score > 30) return 'bg-amber-100 text-amber-800';
    return 'bg-red-100 text-red-800';
}

function lastContactVariant(iso?: string): string {
    if (!iso) return 'text-muted-foreground';
    const days = Math.floor((Date.now() - new Date(iso).getTime()) / 86_400_000);
    if (days <= 7) return 'text-green-600';
    if (days <= 30) return 'text-amber-600';
    return 'text-red-600';
}

function stageColor(stage: string): string {
    const map: Record<string, string> = {
        hot: 'bg-red-500',
        warm: 'bg-orange-400',
        qualified: 'bg-blue-500',
        new: 'bg-gray-400',
        cold: 'bg-slate-400',
        dead: 'bg-gray-300',
        nurture: 'bg-purple-400',
    };
    return map[stage.toLowerCase()] ?? 'bg-gray-400';
}

function formatDate(iso?: string): string {
    if (!iso) return 'Never';
    const d = new Date(iso);
    const days = Math.floor((Date.now() - d.getTime()) / 86_400_000);
    if (days === 0) return 'Today';
    if (days === 1) return '1 day ago';
    return `${days} days ago`;
}

function ActionButton({ action }: { action: C1Action }) {
    if (action.type === 'link' && action.href) {
        return (
            <Link
                href={action.href}
                className="inline-flex items-center rounded border border-border bg-background px-2 py-1 text-xs font-medium hover:bg-muted"
            >
                {action.label}
            </Link>
        );
    }
    return (
        <button
            type="button"
            className="inline-flex items-center rounded border border-border bg-background px-2 py-1 text-xs font-medium hover:bg-muted"
        >
            {action.label}
        </button>
    );
}

export function ContactCard({
    id,
    full_name,
    email,
    phone,
    suburb,
    state,
    stage,
    lead_score,
    last_contacted_at,
    assigned_agent,
    tags = [],
    actions = [],
}: ContactCardProps) {
    return (
        <div className="flex items-start gap-3 rounded-lg border border-border bg-card p-4 shadow-sm">
            {/* Stage dot */}
            <div className="mt-1 flex-shrink-0">
                <span className={cn('block h-3 w-3 rounded-full', stageColor(stage))} title={stage} />
            </div>

            <div className="min-w-0 flex-1 space-y-1">
                {/* Header row */}
                <div className="flex items-center justify-between gap-2">
                    <Link href={`/contacts/${id}`} className="truncate font-semibold hover:underline">
                        {full_name}
                    </Link>
                    {lead_score !== undefined && (
                        <span className={cn('flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-medium', leadScoreVariant(lead_score))}>
                            {lead_score}
                        </span>
                    )}
                </div>

                {/* Contact details */}
                <div className="flex flex-wrap gap-x-3 text-xs text-muted-foreground">
                    {email && <span>{email}</span>}
                    {phone && <span>{phone}</span>}
                    {suburb && <span>{suburb}{state ? `, ${state}` : ''}</span>}
                </div>

                {/* Last contact */}
                <div className="flex items-center gap-2 text-xs">
                    <span className="text-muted-foreground">Last contact:</span>
                    <span className={lastContactVariant(last_contacted_at)}>
                        {formatDate(last_contacted_at)}
                    </span>
                    {assigned_agent && (
                        <>
                            <span className="text-muted-foreground">·</span>
                            <span className="text-muted-foreground">{assigned_agent.name}</span>
                        </>
                    )}
                </div>

                {/* Tags */}
                {tags.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {tags.map((tag) => (
                            <span key={tag} className="rounded-full bg-muted px-2 py-0.5 text-xs">
                                {tag}
                            </span>
                        ))}
                    </div>
                )}

                {/* Actions */}
                {actions.length > 0 && (
                    <div className="flex flex-wrap gap-2 pt-1">
                        {actions.map((action, i) => (
                            <ActionButton key={i} action={action} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
