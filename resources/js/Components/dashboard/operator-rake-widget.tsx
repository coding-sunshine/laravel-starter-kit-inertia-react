import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import rakeLoaderRakes from '@/routes/rake-loader/rakes';

interface OperatorRake {
    rake_id: number;
    rake_number: string;
    siding_name: string;
    state: string;
    loaded: number;
    total: number;
    status: string;
    loading_date: string | null;
}

export function OperatorRakeWidget({ rake }: { rake: OperatorRake | null }) {
    if (!rake) {
        return (
            <Card className="border-0 shadow-sm" style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}>
                <CardContent className="p-6 text-center">
                    <p className="text-sm text-white/60">No active rake assigned.</p>
                </CardContent>
            </Card>
        );
    }

    const pct = rake.total > 0 ? Math.round((rake.loaded / rake.total) * 100) : 0;

    return (
        <Card className="border-0 shadow-sm" style={{ backgroundColor: 'oklch(0.22 0.06 150)' }}>
            <CardContent className="p-6">
                <div className="flex items-start justify-between">
                    <div>
                        <p className="mb-1 text-xs font-semibold uppercase tracking-widest text-white/50">
                            Your Active Rake
                        </p>
                        <p className="font-mono text-2xl font-bold text-white">{rake.rake_number}</p>
                        <p className="mt-0.5 text-sm text-white/60">
                            {rake.siding_name} · {rake.loading_date ?? '—'}
                        </p>
                    </div>
                    <Button asChild className="btn-bgr-gold text-sm">
                        <Link href={rakeLoaderRakes.loading.url({ rake: rake.rake_id })}>Go to Loading →</Link>
                    </Button>
                </div>

                <div className="mt-4">
                    <div className="mb-1 flex justify-between text-xs text-white/60">
                        <span>Loading progress</span>
                        <span className="font-mono tabular-nums">
                            {rake.loaded} / {rake.total} wagons ({pct}%)
                        </span>
                    </div>
                    <div className="h-2 overflow-hidden rounded-full bg-white/20">
                        <div
                            className="h-full rounded-full transition-all"
                            style={{ width: `${pct}%`, backgroundColor: 'oklch(0.72 0.12 80)' }}
                        />
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
