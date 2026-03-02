'use client';

import { LazyMotion, domAnimation, m } from 'framer-motion';
import type { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { useReducedMotion } from '@/hooks/use-reduced-motion';

const PAGE_TRANSITION_MS = 200;

interface PageTransitionProps {
    children: ReactNode;
}

/**
 * Wraps Inertia page content with a fade transition. Respects prefers-reduced-motion.
 * Use as the single child of AnimatePresence; key must be the page URL so exit runs.
 */
export function PageTransition({ children }: PageTransitionProps): ReactNode {
    const { url } = usePage();
    const reducedMotion = useReducedMotion();

    const transition = reducedMotion
        ? { duration: 0 }
        : { duration: PAGE_TRANSITION_MS / 1000, ease: 'easeOut' as const };

    return (
        <LazyMotion features={domAnimation} strict>
            <m.div
                key={url}
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={transition}
                className="contents"
            >
                {children}
            </m.div>
        </LazyMotion>
    );
}
