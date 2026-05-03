import {
    ArrowDownCircle,
    ArrowUpCircle,
    TrendingUp,
    Weight,
} from 'lucide-react';

import { KpiTile } from '@/components/control-room/KpiTile';

export interface SummaryTilesProps {
    totalNetMt: number;
    avgNetMt: number | null;
    totalOverloadMt: number;
    totalUnderloadMt: number;
    compact?: boolean;
}

export function SummaryTiles({
    totalNetMt,
    avgNetMt,
    totalOverloadMt,
    totalUnderloadMt,
    compact = false,
}: SummaryTilesProps) {
    return (
        <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <KpiTile
                icon={Weight}
                label="Total Net Weight"
                value={totalNetMt}
                unit="MT"
                accentColor="blue"
                decimals={2}
                compact={compact}
            />
            <KpiTile
                icon={TrendingUp}
                label="Avg Net / Wagon"
                value={avgNetMt}
                unit="MT"
                accentColor="purple"
                decimals={2}
                compact={compact}
            />
            <KpiTile
                icon={ArrowUpCircle}
                label="Total Over Load"
                value={totalOverloadMt}
                unit="MT"
                accentColor="red"
                decimals={2}
                compact={compact}
            />
            <KpiTile
                icon={ArrowDownCircle}
                label="Total Under Load"
                value={totalUnderloadMt}
                unit="MT"
                accentColor="amber"
                decimals={2}
                compact={compact}
            />
        </div>
    );
}

export default SummaryTiles;
