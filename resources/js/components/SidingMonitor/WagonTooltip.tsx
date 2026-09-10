import { AnimatePresence, motion } from 'framer-motion';

interface Props {
    wagon: {
        sequence: number;
        loadriteWeightMt: number | null;
        loadedQuantityMt: number | null;
        ccCapacityMt: number;
        weightSource: string;
        percentage: number;
    };
    visible: boolean;
}

export function WagonTooltip({ wagon, visible }: Props) {
    const weight = wagon.loadriteWeightMt ?? wagon.loadedQuantityMt ?? 0;
    const sourceLabel = { manual: 'Manual', loadrite: 'Loadrite', weighbridge: 'Weighbridge' }[wagon.weightSource] ?? wagon.weightSource;

    return (
        <AnimatePresence>
            {visible && (
                <motion.div
                    initial={{ opacity: 0, scale: 0.95, y: 4 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.95, y: 4 }}
                    transition={{ duration: 0.15, ease: 'easeOut' }}
                    className="absolute bottom-full left-1/2 mb-2 -translate-x-1/2 z-50 w-44 rounded-lg border border-slate-700 bg-slate-800 p-3 shadow-xl"
                    role="tooltip"
                >
                    <p className="mb-1 font-mono text-xs font-semibold text-slate-200">
                        Wagon #{wagon.sequence}
                    </p>
                    <div className="space-y-0.5 text-xs text-slate-400">
                        <div className="flex justify-between">
                            <span>Weight</span>
                            <span className="font-mono text-slate-200">{weight.toFixed(1)} MT</span>
                        </div>
                        <div className="flex justify-between">
                            <span>CC</span>
                            <span className="font-mono text-slate-200">{wagon.ccCapacityMt.toFixed(1)} MT</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Loading</span>
                            <span className={`font-mono font-semibold ${wagon.percentage >= 100 ? 'text-red-400' : wagon.percentage >= 90 ? 'text-amber-400' : 'text-green-400'}`}>
                                {wagon.percentage.toFixed(1)}%
                            </span>
                        </div>
                        <div className="flex justify-between">
                            <span>Source</span>
                            <span className="rounded bg-slate-700 px-1 text-slate-300">{sourceLabel}</span>
                        </div>
                    </div>
                    <div className="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-slate-700" />
                </motion.div>
            )}
        </AnimatePresence>
    );
}
