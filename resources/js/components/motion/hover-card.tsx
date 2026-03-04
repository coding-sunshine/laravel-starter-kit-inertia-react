'use client';

import { useReducedMotion } from '@/hooks/use-reduced-motion';
import { LazyMotion, domAnimation, m } from 'framer-motion';
import type { ReactNode } from 'react';

interface HoverCardProps {
    children: ReactNode;
    className?: string;
}

/**
 * Wraps content with subtle hover (y: -2) and tap (scale 0.98) feedback. Respects reduced motion.
 */
export function HoverCard({ children, className }: HoverCardProps): ReactNode {
    const reduced = useReducedMotion();

    return (
        <LazyMotion features={domAnimation} strict>
            <m.div
                className={className}
                whileHover={
                    reduced
                        ? undefined
                        : { y: -2, transition: { duration: 0.2 } }
                }
                whileTap={
                    reduced
                        ? undefined
                        : { scale: 0.99, transition: { duration: 0.1 } }
                }
            >
                {children}
            </m.div>
        </LazyMotion>
    );
}
