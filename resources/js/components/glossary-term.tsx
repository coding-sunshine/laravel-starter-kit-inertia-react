import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { rrmcsGlossary } from '@/lib/rrmcs-glossary';
import type { ReactNode } from 'react';

interface GlossaryTermProps {
    /** Key in rrmcsGlossary (case-sensitive). */
    term: string;
    children: ReactNode;
}

/**
 * Wraps text with a dotted-underline tooltip showing the glossary definition.
 * Falls through to plain rendering if the term is not found.
 */
export function GlossaryTerm({ term, children }: GlossaryTermProps) {
    const definition = rrmcsGlossary[term];

    if (!definition) {
        return <>{children}</>;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className="cursor-help border-b border-dotted border-current">
                    {children}
                </span>
            </TooltipTrigger>
            <TooltipContent>{definition}</TooltipContent>
        </Tooltip>
    );
}
