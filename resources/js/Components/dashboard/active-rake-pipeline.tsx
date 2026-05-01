import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Train } from 'lucide-react';

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

const inr = new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 });

function RakeCard({ card }: { card: RakePipelineCard }) {
    const hasOverload = card.overloaded_count > 0;

    return (
        <div
            className={`rounded-md border p-2.5 text-sm transition-colors ${
                hasOverload
                    ? 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20'
                    : 'border-border bg-card hover:border-primary/30'
            }`}
        >
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="font-mono text-sm font-semibold text-foreground">{card.rake_number}</p>
                    <p className="truncate text-[11px] text-muted-foreground">
                        {card.siding_code} · {card.wagon_count} wagons
                        {card.loading_date ? ` · ${card.loading_date}` : ''}
                    </p>
                </div>
                {hasOverload && (
                    <Badge variant="destructive" className="shrink-0 text-[10px]">
                        {card.overloaded_count} OL
                    </Badge>
                )}
            </div>
            {hasOverload && card.penalty_risk_rs > 0 && (
                <p className="mt-1.5 font-mono text-[11px] font-semibold tabular-nums text-rose-600 dark:text-rose-400">
                    {inr.format(card.penalty_risk_rs)} risk
                </p>
            )}
        </div>
    );
}

const COLUMNS: { key: keyof ActiveRakePipeline; label: string; dotClass: string }[] = [
    { key: 'loading', label: 'Loading', dotClass: 'bg-blue-500' },
    { key: 'awaiting_clearance', label: 'Awaiting clearance', dotClass: 'bg-amber-500' },
    { key: 'dispatched', label: 'Dispatched today', dotClass: 'bg-emerald-500' },
];

function Column({
    label,
    dotClass,
    cards,
}: {
    label: string;
    dotClass: string;
    cards: RakePipelineCard[];
}) {
    return (
        <section className="flex min-w-0 flex-col">
            <header className="mb-2 flex items-center justify-between gap-2 border-b pb-1.5">
                <div className="flex items-center gap-2">
                    <span className={`h-2 w-2 rounded-full ${dotClass}`} aria-hidden="true" />
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {label}
                    </p>
                </div>
                <span className="rounded-full bg-muted px-2 py-0.5 font-mono text-[10px] font-semibold tabular-nums text-foreground">
                    {cards.length}
                </span>
            </header>

            {cards.length === 0 ? (
                <div className="flex flex-1 flex-col items-center justify-center gap-1 rounded-md border border-dashed py-6 text-center">
                    <Train className="h-4 w-4 text-muted-foreground/40" aria-hidden="true" />
                    <p className="text-[11px] text-muted-foreground/70">No rakes</p>
                </div>
            ) : (
                <div className="flex max-h-[480px] flex-col gap-1.5 overflow-y-auto pr-1">
                    {cards.map((card) => (
                        <RakeCard key={card.rake_id} card={card} />
                    ))}
                </div>
            )}
        </section>
    );
}

export function ActiveRakePipeline({ data }: { data: ActiveRakePipeline }) {
    const totalActive = data.loading.length + data.awaiting_clearance.length + data.dispatched.length;

    return (
        <Card className="shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                <CardTitle className="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-emerald-900 dark:text-emerald-300">
                    <Train className="h-4 w-4" aria-hidden="true" />
                    Active rake pipeline
                </CardTitle>
                <span className="rounded-full bg-muted px-2 py-0.5 font-mono text-[11px] font-semibold tabular-nums text-foreground">
                    {totalActive} total
                </span>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    {COLUMNS.map((col) => (
                        <Column key={col.key} label={col.label} dotClass={col.dotClass} cards={data[col.key]} />
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
