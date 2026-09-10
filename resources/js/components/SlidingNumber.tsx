import React, { useEffect, useState } from 'react';

interface SlidingNumberProps {
    value: number;
    format?: (value: number) => string;
    durationMs?: number;
}

export function SlidingNumber({ value, format, durationMs = 300 }: SlidingNumberProps) {
    const [displayValue, setDisplayValue] = useState(value);
    const [previousValue, setPreviousValue] = useState<number | null>(null);
    const [isAnimating, setIsAnimating] = useState(false);

    useEffect(() => {
        if (value === displayValue) {
            return;
        }

        setPreviousValue(displayValue);
        setIsAnimating(true);

        const timeout = setTimeout(() => {
            setDisplayValue(value);
            setPreviousValue(null);
            setIsAnimating(false);
        }, durationMs);

        return () => clearTimeout(timeout);
    }, [value, displayValue, durationMs]);

    const formatValue = (v: number) => (format ? format(v) : v.toLocaleString());

    if (!isAnimating || previousValue === null) {
        return (
            <span className="inline-block overflow-hidden align-baseline">
                <span className="inline-block leading-tight">{formatValue(displayValue)}</span>
            </span>
        );
    }

    return (
        <span className="inline-block h-[1.35em] overflow-hidden align-baseline">
            <span className="inline-flex flex-col transition-transform duration-300 ease-out translate-y-[-100%]">
                <span className="leading-tight opacity-60">{formatValue(previousValue)}</span>
                <span className="leading-tight">{formatValue(value)}</span>
            </span>
        </span>
    );
}

