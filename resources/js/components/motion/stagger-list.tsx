'use client';

import { useReducedMotion } from '@/hooks/use-reduced-motion';
import { LazyMotion, domAnimation, m } from 'framer-motion';
import type { ReactNode } from 'react';

const DEFAULT_STAGGER_DELAY = 0.03;
const DEFAULT_DURATION = 0.2;

interface StaggerListProps {
    children: ReactNode;
    className?: string;
    /** Delay between each child animation in seconds (default: 0.03) */
    staggerDelay?: number;
    /** Delay before the first child starts animating in seconds (default: 0) */
    delayChildren?: number;
}

/**
 * Wraps a list/grid so children animate in with a short stagger. Respects reduced motion.
 */
export function StaggerList({
    children,
    className,
    staggerDelay = DEFAULT_STAGGER_DELAY,
    delayChildren = 0,
}: StaggerListProps): ReactNode {
    const reduced = useReducedMotion();

    const container = {
        hidden: { opacity: 0 },
        show: {
            opacity: 1,
            transition: reduced
                ? { duration: 0 }
                : { staggerChildren: staggerDelay, delayChildren },
        },
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
    show: {
        opacity: 1,
        y: 0,
        transition: { duration: DEFAULT_DURATION, ease: 'easeOut' as const },
    },
};

export function StaggerItem({
    children,
    className,
    duration,
}: {
    children: ReactNode;
    className?: string;
    /** Animation duration in seconds (default: 0.2) */
    duration?: number;
}): ReactNode {
    const reduced = useReducedMotion();

    const variants =
        duration != null
            ? {
                  hidden: { opacity: 0, y: 8 },
                  show: {
                      opacity: 1,
                      y: 0,
                      transition: {
                          duration,
                          ease: 'easeOut' as const,
                      },
                  },
              }
            : staggerItemVariants;

    return (
        <m.div
            className={className}
            variants={reduced ? undefined : variants}
        >
            {children}
        </m.div>
    );
}
