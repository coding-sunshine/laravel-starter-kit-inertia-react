import { motion, useReducedMotion } from 'framer-motion';
import { useState } from 'react';
import type { WagonSlice } from '@/stores/useSidingStore';
import { WagonTooltip } from './WagonTooltip';

interface Props {
    wagon: WagonSlice;
}

function getBlockStyle(wagon: WagonSlice): { bg: string; border: string } {
    if (wagon.weightSource === 'weighbridge') return { bg: 'bg-blue-950', border: 'border-blue-600' };
    if (wagon.percentage >= 100) return { bg: 'bg-red-950', border: 'border-red-500' };
    if (wagon.percentage >= 90) return { bg: 'bg-amber-950', border: 'border-amber-500' };
    if (wagon.weightSource === 'loadrite') return { bg: 'bg-green-900', border: 'border-green-500' };
    if (wagon.weightSource === 'manual' && wagon.loadedQuantityMt !== null) return { bg: 'bg-green-950', border: 'border-green-700' };
    return { bg: 'bg-slate-800', border: 'border-slate-600' };
}

function getAnimateVariant(wagon: WagonSlice) {
    if (wagon.percentage >= 100) return 'critical';
    if (wagon.percentage >= 90) return 'warning';
    return 'idle';
}

const variants = {
    idle: { scale: 1, x: 0 },
    warning: { scale: [1, 1.08, 1] as number[] },
    critical: { x: [-4, 4, -4, 4, 0] as number[] },
};

export function WagonBlock({ wagon }: Props) {
    const [tooltipVisible, setTooltipVisible] = useState(false);
    const shouldReduceMotion = useReducedMotion();
    const { bg, border } = getBlockStyle(wagon);
    const animateKey = getAnimateVariant(wagon);

    return (
        <div
            className="relative"
            role="button"
            tabIndex={0}
            onMouseEnter={() => setTooltipVisible(true)}
            onMouseLeave={() => setTooltipVisible(false)}
            onClick={() => setTooltipVisible((v) => !v)}
            onKeyDown={(e) => e.key === 'Enter' && setTooltipVisible((v) => !v)}
        >
            <motion.div
                key={`${wagon.sequence}-${wagon.loadriteWeightMt}`}
                animate={shouldReduceMotion ? 'idle' : animateKey}
                variants={variants}
                transition={{ duration: 0.25, ease: 'easeOut' }}
                className={`
                    h-7 w-7 rounded border cursor-pointer
                    sm:h-10 sm:w-10
                    lg:h-12 lg:w-12
                    ${bg} ${border}
                    flex items-center justify-center
                    transition-colors duration-200
                `}
            >
                <span className="hidden text-[10px] font-mono text-slate-400 sm:block lg:text-xs">
                    {wagon.sequence}
                </span>
            </motion.div>
            <WagonTooltip wagon={wagon} visible={tooltipVisible} />
        </div>
    );
}
