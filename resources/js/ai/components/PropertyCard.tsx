/**
 * C1 PropertyCard component — renders a lot or project card with price, stats, and actions.
 */

import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PropertyCardProps, C1Action } from '../c1-types';

function formatPrice(amount?: number, currency = 'AUD'): string {
    if (amount === undefined) return '—';
    return new Intl.NumberFormat('en-AU', { style: 'currency', currency, maximumFractionDigits: 0 }).format(amount);
}

function statusVariant(status?: string): string {
    const map: Record<string, string> = {
        available: 'bg-green-100 text-green-800',
        reserved: 'bg-amber-100 text-amber-800',
        sold: 'bg-red-100 text-red-800',
    };
    return status ? (map[status] ?? 'bg-muted text-muted-foreground') : '';
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

export function PropertyCard({
    id,
    type,
    title,
    suburb,
    state,
    stage,
    title_status,
    price,
    min_price,
    bedrooms,
    bathrooms,
    car,
    total_m2,
    project_title,
    is_hot_property,
    available_lots_count,
    actions = [],
}: PropertyCardProps) {
    const href = type === 'lot' ? `/lots/${id}` : `/projects/${id}`;
    const displayPrice = type === 'lot' ? price : min_price;

    return (
        <div className="relative rounded-lg border border-border bg-card p-4 shadow-sm">
            {/* Hot badge */}
            {is_hot_property && (
                <span className="absolute right-3 top-3 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                    Hot
                </span>
            )}

            <div className="space-y-2">
                {/* Title & project */}
                <div>
                    <Link href={href} className="font-semibold hover:underline">
                        {title}
                    </Link>
                    {project_title && (
                        <p className="text-xs text-muted-foreground">{project_title}</p>
                    )}
                </div>

                {/* Location */}
                {(suburb || state) && (
                    <p className="text-xs text-muted-foreground">
                        {[suburb, state].filter(Boolean).join(', ')}
                    </p>
                )}

                {/* Status badge */}
                {title_status && (
                    <span className={cn('inline-block rounded-full px-2 py-0.5 text-xs font-medium capitalize', statusVariant(title_status))}>
                        {title_status}
                    </span>
                )}

                {/* Stats row */}
                <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                    {bedrooms !== undefined && <span>🛏 {bedrooms}</span>}
                    {bathrooms !== undefined && <span>🚿 {bathrooms}</span>}
                    {car !== undefined && <span>🚗 {car}</span>}
                    {total_m2 !== undefined && <span>{total_m2} m²</span>}
                    {available_lots_count !== undefined && <span>{available_lots_count} lots available</span>}
                </div>

                {/* Price */}
                {displayPrice !== undefined && (
                    <p className="text-sm font-semibold">
                        {type === 'project' ? 'From ' : ''}{formatPrice(displayPrice)}
                    </p>
                )}

                {/* Stage (for projects) */}
                {stage && <p className="text-xs text-muted-foreground capitalize">{stage.replace('_', ' ')}</p>}

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
