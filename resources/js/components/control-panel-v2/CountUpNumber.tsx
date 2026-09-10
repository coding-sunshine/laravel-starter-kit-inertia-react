import {
    animate,
    motion,
    useMotionValue,
    useTransform,
} from 'framer-motion';
import { useEffect, useRef } from 'react';

interface Props {
    value: number;
    decimals?: number;
    suffix?: string;
    className?: string;
    durationMs?: number;
}

/**
 * Spring count-up number. MotionValue stored via useRef-equivalent
 * (useMotionValue holds its identity across renders by default) so parent
 * re-renders do not retrigger the animation; only value changes do.
 */
export function CountUpNumber({
    value,
    decimals = 0,
    suffix,
    className,
    durationMs = 800,
}: Props) {
    const motionValue = useMotionValue(value);
    const display = useTransform(motionValue, (n) => formatNumber(n, decimals));
    const previous = useRef<number>(value);

    useEffect(() => {
        if (previous.current === value) return;
        const controls = animate(motionValue, value, {
            duration: durationMs / 1000,
            ease: 'easeOut',
        });
        previous.current = value;
        return () => controls.stop();
    }, [value, durationMs, motionValue]);

    return (
        <span className={className}>
            <motion.span>{display}</motion.span>
            {suffix ? <span className="ml-1 opacity-70">{suffix}</span> : null}
        </span>
    );
}

function formatNumber(n: number, decimals: number): string {
    return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(n);
}
