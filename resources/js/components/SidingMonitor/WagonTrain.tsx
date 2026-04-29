import { motion, useReducedMotion } from 'framer-motion';
import type { WagonSlice } from '@/stores/useSidingStore';
import { WagonBlock } from './WagonBlock';

interface Props {
    wagons: Record<number, WagonSlice>;
}

export function WagonTrain({ wagons }: Props) {
    const shouldReduceMotion = useReducedMotion();
    const sortedWagons = Object.values(wagons).sort((a, b) => a.sequence - b.sequence);

    return (
        <div className="w-full overflow-x-auto pb-2">
            {/* Tablet+: horizontal row */}
            <div className="hidden sm:flex flex-nowrap gap-1.5 min-w-max">
                {sortedWagons.map((wagon, i) => (
                    <motion.div
                        key={wagon.sequence}
                        initial={{ opacity: 0, x: -8 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={shouldReduceMotion ? { duration: 0 } : { delay: i * 0.03, duration: 0.2, ease: 'easeOut' }}
                    >
                        <WagonBlock wagon={wagon} />
                    </motion.div>
                ))}
            </div>
            {/* Mobile: wrap grid, 8 per row */}
            <div className="grid grid-cols-8 gap-1 sm:hidden">
                {sortedWagons.map((wagon) => (
                    <WagonBlock key={wagon.sequence} wagon={wagon} />
                ))}
            </div>
        </div>
    );
}
