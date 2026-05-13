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
        <div
            className="grid gap-1.5"
            style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(44px, 1fr))' }}
        >
            {sortedWagons.map((wagon, i) => (
                <motion.div
                    key={wagon.sequence}
                    initial={{ opacity: 0, scale: 0.7 }}
                    animate={{ opacity: 1, scale: 1 }}
                    transition={shouldReduceMotion ? { duration: 0 } : { delay: i * 0.02, duration: 0.18, ease: 'easeOut' }}
                >
                    <WagonBlock wagon={wagon} />
                </motion.div>
            ))}
        </div>
    );
}
