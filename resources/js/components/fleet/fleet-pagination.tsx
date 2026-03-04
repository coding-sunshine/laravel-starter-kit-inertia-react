'use client';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import * as React from 'react';

export interface FleetPaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface FleetPaginationProps {
    links: FleetPaginationLink[];
    /** e.g. "Showing 1 to 10 of 45 entries" */
    showingLabel?: React.ReactNode;
    /** Optional left-side content (e.g. page size Select) */
    leftContent?: React.ReactNode;
    className?: string;
}

/**
 * Pagination bar (reference BusMasterPage): optional "Showing X–Y of Z", prev/next + page numbers.
 * Uses Laravel pagination links array.
 */
export function FleetPagination({
    links,
    showingLabel,
    leftContent,
    className,
}: FleetPaginationProps) {
    if (!links || links.length <= 1) return null;

    const prev = links[0];
    const next = links[links.length - 1];
    const pages = links.slice(1, -1);

    return (
        <div
            className={cn(
                'flex flex-wrap items-center justify-between gap-3 border-t border-white/30 px-4 py-3',
                className,
            )}
        >
            <div className="flex flex-wrap items-center gap-4">
                {leftContent}
                {showingLabel != null && (
                    <span className="text-sm text-muted-foreground">
                        {showingLabel}
                    </span>
                )}
            </div>
            <div className="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="icon"
                    className="size-8 shrink-0"
                    disabled={!prev?.url}
                    asChild={!!prev?.url}
                >
                    {prev?.url ? (
                        <Link href={prev.url} preserveScroll>
                            <ChevronLeft className="size-4" />
                            <span className="sr-only">Previous</span>
                        </Link>
                    ) : (
                        <span>
                            <ChevronLeft className="size-4" />
                            <span className="sr-only">Previous</span>
                        </span>
                    )}
                </Button>
                {pages.map((link, i) =>
                    link.label === '...' ? (
                        <span
                            key={`ellipsis-${i}`}
                            className="px-2 text-muted-foreground"
                        >
                            …
                        </span>
                    ) : link.active ? (
                        <Button
                            key={i}
                            variant="default"
                            size="icon"
                            className="size-8 min-w-8 shrink-0"
                        >
                            {link.label}
                        </Button>
                    ) : link.url ? (
                        <Button
                            key={i}
                            variant="outline"
                            size="icon"
                            className="size-8 min-w-8 shrink-0"
                            asChild
                        >
                            <Link href={link.url} preserveScroll>
                                {link.label}
                            </Link>
                        </Button>
                    ) : (
                        <span key={i} className="size-8 min-w-8 shrink-0" />
                    ),
                )}
                <Button
                    variant="outline"
                    size="icon"
                    className="size-8 shrink-0"
                    disabled={!next?.url}
                    asChild={!!next?.url}
                >
                    {next?.url ? (
                        <Link href={next.url} preserveScroll>
                            <ChevronRight className="size-4" />
                            <span className="sr-only">Next</span>
                        </Link>
                    ) : (
                        <span>
                            <ChevronRight className="size-4" />
                            <span className="sr-only">Next</span>
                        </span>
                    )}
                </Button>
            </div>
        </div>
    );
}
