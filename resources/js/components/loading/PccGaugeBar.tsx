import { gaugeColorForPcc } from '@/utils/pcc';

interface PccGaugeBarProps {
    pct: number;
    loadedMt: number;
    pccMt: number;
}

export function PccGaugeBar({ pct, loadedMt, pccMt }: PccGaugeBarProps) {
    const clampedPct = Math.min(pct, 100);
    const color = gaugeColorForPcc(loadedMt, pccMt);
    const label = pccMt > 0 ? `${pct.toFixed(1)}%` : '—';

    return (
        <div className="flex flex-col gap-0.5">
            <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                    className="h-full rounded-full transition-all duration-150"
                    style={{ width: `${clampedPct}%`, backgroundColor: color }}
                />
            </div>
            <span className="font-mono text-[10px] tabular-nums" style={{ color }}>
                {label}
            </span>
        </div>
    );
}
