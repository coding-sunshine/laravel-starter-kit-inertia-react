import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface RakePipelineCard {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    siding_code: string;
    wagon_count: number;
    overloaded_count: number;
    penalty_risk_rs: number;
    state: string;
    loading_date: string | null;
}

interface ActiveRakePipeline {
    loading: RakePipelineCard[];
    awaiting_clearance: RakePipelineCard[];
    dispatched: RakePipelineCard[];
}

function RakeCard({ card }: { card: RakePipelineCard }) {
    const hasOverload = card.overloaded_count > 0;
    const riskFormatted = new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0,
    }).format(card.penalty_risk_rs);

    return (
        <div
            className={`rounded-lg border p-3 text-sm transition-colors ${
                hasOverload ? 'border-red-200 bg-red-50' : 'border-gray-100 bg-white'
            }`}
        >
            <div className="flex items-start justify-between gap-2">
                <div>
                    <p className="font-mono font-semibold text-gray-900">{card.rake_number}</p>
                    <p className="text-[11px] text-gray-500">
                        {card.siding_code} · {card.loading_date ?? '—'}
                    </p>
                </div>
                {hasOverload && (
                    <Badge variant="destructive" className="shrink-0 text-[10px]">
                        {card.overloaded_count} overloaded
                    </Badge>
                )}
            </div>
            <div className="mt-2 flex items-center justify-between text-[11px] text-gray-500">
                <span>{card.wagon_count} wagons</span>
                {hasOverload && (
                    <span className="font-mono font-semibold tabular-nums text-red-600">{riskFormatted} risk</span>
                )}
            </div>
        </div>
    );
}

const COLUMNS: { key: keyof ActiveRakePipeline; label: string; color: string }[] = [
    { key: 'loading', label: 'Loading', color: 'text-blue-600' },
    { key: 'awaiting_clearance', label: 'Awaiting Clearance', color: 'text-amber-600' },
    { key: 'dispatched', label: 'Dispatched Today', color: 'text-green-600' },
];

export function ActiveRakePipeline({ data }: { data: ActiveRakePipeline }) {
    return (
        <Card className="shadow-sm">
            <CardHeader className="pb-2">
                <CardTitle
                    className="text-sm font-semibold uppercase tracking-wide"
                    style={{ color: 'oklch(0.22 0.06 150)' }}
                >
                    Active Rake Pipeline
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-3 gap-4">
                    {COLUMNS.map((col) => (
                        <div key={col.key}>
                            <div className="mb-2 flex items-center justify-between">
                                <p className={`text-xs font-semibold uppercase tracking-wide ${col.color}`}>
                                    {col.label}
                                </p>
                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">
                                    {data[col.key].length}
                                </span>
                            </div>
                            <div className="flex flex-col gap-2">
                                {data[col.key].length === 0 ? (
                                    <p className="text-[11px] italic text-gray-400">No rakes</p>
                                ) : (
                                    data[col.key].map((card) => <RakeCard key={card.rake_id} card={card} />)
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
