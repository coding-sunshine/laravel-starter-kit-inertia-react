import { AnimatePresence, motion, useReducedMotion } from 'framer-motion';
import { useMemo } from 'react';

interface Props {
    triggerKey: string | number | null;
    particleCount?: number;
}

const COLORS = [
    '#10B981',
    '#0EA5E9',
    '#F59E0B',
    '#EF4444',
    '#8B5CF6',
    '#FBBF24',
    '#22D3EE',
];

/**
 * Fires a one-shot SVG particle burst whenever `triggerKey` transitions to
 * a non-null value. Reduced-motion users see no burst.
 */
export function ConfettiBurst({ triggerKey, particleCount = 36 }: Props) {
    const reducedMotion = useReducedMotion() ?? false;

    const particles = useMemo(() => {
        return Array.from({ length: particleCount }).map((_, i) => {
            const angle = (Math.PI * 2 * i) / particleCount + Math.random() * 0.4;
            const distance = 180 + Math.random() * 200;
            return {
                i,
                dx: Math.cos(angle) * distance,
                dy: Math.sin(angle) * distance - 60,
                rotate: Math.random() * 720 - 360,
                color: COLORS[i % COLORS.length],
                size: 4 + Math.random() * 5,
                duration: 1.4 + Math.random() * 1.0,
            };
        });
    }, [particleCount]);

    if (reducedMotion) return null;

    return (
        <AnimatePresence>
            {triggerKey != null && (
                <motion.div
                    key={triggerKey}
                    className="pointer-events-none fixed inset-0 z-[60] flex items-center justify-center"
                    initial={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                >
                    <svg width="100%" height="100%" className="overflow-visible">
                        {particles.map((p) => (
                            <motion.rect
                                key={p.i}
                                x="50%"
                                y="40%"
                                width={p.size}
                                height={p.size * 0.6}
                                fill={p.color}
                                initial={{ x: 0, y: 0, opacity: 1, rotate: 0 }}
                                animate={{
                                    x: p.dx,
                                    y: p.dy + 320,
                                    opacity: 0,
                                    rotate: p.rotate,
                                }}
                                transition={{
                                    duration: p.duration,
                                    ease: [0.2, 0.6, 0.4, 1],
                                }}
                            />
                        ))}
                    </svg>
                </motion.div>
            )}
        </AnimatePresence>
    );
}
