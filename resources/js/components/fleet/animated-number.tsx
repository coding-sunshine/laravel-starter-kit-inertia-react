'use client';

import { useReducedMotion } from '@/hooks/use-reduced-motion';
import { useInView, useMotionValue, useSpring } from 'framer-motion';
import { useEffect, useRef, type ReactNode } from 'react';

interface AnimatedNumberProps {
    value: number;
    duration?: number;
    prefix?: string;
    suffix?: string;
    className?: string;
}

/**
 * Animates a number from 0 to its target value on mount and when value changes.
 * Uses framer-motion spring animation with easing for a natural feel.
 * Respects prefers-reduced-motion.
 */
export function AnimatedNumber({
    value,
    duration = 400,
    prefix,
    suffix,
    className,
}: AnimatedNumberProps): ReactNode {
    const ref = useRef<HTMLSpanElement>(null);
    const reduced = useReducedMotion();
    const inView = useInView(ref, { once: true });

    const motionValue = useMotionValue(0);
    const springValue = useSpring(motionValue, {
        duration: reduced ? 0 : duration,
        bounce: 0,
    });

    const isInteger = Number.isInteger(value);
    const decimals = isInteger ? 0 : (value.toString().split('.')[1]?.length ?? 2);

    useEffect(() => {
        if (inView) {
            motionValue.set(value);
        }
    }, [inView, value, motionValue]);

    useEffect(() => {
        const unsubscribe = springValue.on('change', (latest) => {
            if (ref.current) {
                const formatted = isInteger
                    ? Math.round(latest).toLocaleString()
                    : latest.toFixed(decimals);
                ref.current.textContent = `${prefix ?? ''}${formatted}${suffix ?? ''}`;
            }
        });
        return unsubscribe;
    }, [springValue, isInteger, decimals, prefix, suffix]);

    const initialDisplay = `${prefix ?? ''}${reduced ? (isInteger ? value.toLocaleString() : value.toFixed(decimals)) : '0'}${suffix ?? ''}`;

    return (
        <span ref={ref} className={className}>
            {initialDisplay}
        </span>
    );
}
