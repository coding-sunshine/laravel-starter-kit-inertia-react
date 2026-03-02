'use client';

import { LazyMotion, domAnimation, m } from 'framer-motion';
import type { ReactNode } from 'react';
import { useReducedMotion } from '@/hooks/use-reduced-motion';

const STAGGER_DELAY = 0.03;
const DURATION = 0.2;

interface StaggerListProps {
    children: ReactNode;
    className?: string;
}

/**
 * Wraps a list/grid so children animate in with a short stagger. Respects reduced motion.
 */
export function StaggerList({ children, className }: StaggerListProps): ReactNode {
    const reduced = useReducedMotion();

    const container = {
        hidden: { opacity: 0 },
        show: {
            opacity: 1,
            transition: reduced
                ? { duration: 0 }
                : { staggerChildren: STAGGER_DELAY, delayChildren: 0 },
        },
    };

    const item = {
        hidden: reduced ? {} : { opacity: 0, y: 8 },
        show: reduced ? {} : { opacity: 1, y: 0, transition: { duration: DURATION, ease: 'easeOut' } },
    };

    return (
        <LazyMotion features={domAnimation} strict>
            <m.div
                className={className}
                variants={container}
                initial="hidden"
                animate="show"
            >
                {children}
            </m.div>
        </LazyMotion>
    );
}

/**
 * Wrap each list/grid item with this so stagger works. Use variants from parent StaggerList.
 */
export const staggerItemVariants = {
    hidden: { opacity: 0, y: 8 },
    show: { opacity: 1, y: 0, transition: { duration: DURATION, ease: 'easeOut' as const } },
};

export function StaggerItem({
    children,
    className,
}: { children: ReactNode; className?: string }): ReactNode {
    const reduced = useReducedMotion();
    return (
        <m.div
            className={className}
            variants={reduced ? undefined : staggerItemVariants}
        >
            {children}
        </m.div>
    );
}
