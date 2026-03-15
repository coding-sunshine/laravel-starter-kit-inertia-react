import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import { Loader2, MapPin, RefreshCw, Sparkles, TrendingUp } from 'lucide-react';
import { useState } from 'react';

interface SuburbAiData {
    id: number;
    suburb_name: string;
    state: string | null;
    postcode: string | null;
    source: string;
    median_house_price: string | null;
    median_unit_price: string | null;
    median_rent_house: string | null;
    median_rent_unit: string | null;
    rental_yield: string | null;
    annual_growth: string | null;
    ai_insights: {
        market_summary?: string;
        demand_level?: string;
        vacancy_rate?: number;
        days_on_market?: number;
    } | null;
    fetched_at: string | null;
}

interface Props {
    suburb_data: {
        data: SuburbAiData[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Suburb AI Insights', href: '/suburb-ai' },
];

function formatCurrency(value: string | null): string {
    if (!value) return '—';
    return new Intl.NumberFormat('en-AU', { style: 'currency', currency: 'AUD', maximumFractionDigits: 0 }).format(
        parseFloat(value),
    );
}

export default function SuburbAiIndexPage({ suburb_data }: Props) {
    const [suburb, setSuburb] = useState('');
    const [state, setState] = useState('');
    const [postcode, setPostcode] = useState('');
    const [isFetching, setIsFetching] = useState(false);
    const [result, setResult] = useState<SuburbAiData | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleFetch = async (forceRefresh = false) => {
        if (!suburb.trim()) return;

        setIsFetching(true);
        setError(null);

        try {
            const res = await axios.post('/suburb-ai/fetch', {
                suburb: suburb.trim(),
                state: state.trim() || undefined,
                postcode: postcode.trim() || undefined,
                force_refresh: forceRefresh,
            });
            setResult(res.data.data);
            router.reload({ only: ['suburb_data'] });
        } catch {
            setError('Failed to fetch suburb data. Please try again.');
        } finally {
            setIsFetching(false);
        }
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Suburb AI Insights" />

            <div className="space-y-6 p-6">
                <div className="flex items-center gap-3">
                    <Sparkles className="text-primary h-6 w-6" />
                    <div>
                        <h1 className="text-2xl font-bold">Suburb AI Insights</h1>
                        <p className="text-muted-foreground text-sm">
                            AI-powered market data including median prices, rental yields, and growth trends.
                        </p>
                    </div>
                </div>

                {/* Search Panel */}
                <div className="rounded-lg border bg-card p-5">
                    <h2 className="mb-4 font-semibold">Fetch Suburb Data</h2>
                    <div className="flex flex-wrap gap-3">
                        <div className="flex-1 min-w-[200px]">
                            <label className="text-muted-foreground mb-1 block text-xs font-medium">Suburb *</label>
                            <input
                                type="text"
                                value={suburb}
                                onChange={(e) => setSuburb(e.target.value)}
                                placeholder="e.g. South Yarra"
                                className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                onKeyDown={(e) => e.key === 'Enter' && handleFetch()}
                            />
                        </div>
                        <div className="w-32">
                            <label className="text-muted-foreground mb-1 block text-xs font-medium">State</label>
                            <select
                                value={state}
                                onChange={(e) => setState(e.target.value)}
                                className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="">Any</option>
                                {['NSW', 'VIC', 'QLD', 'WA', 'SA', 'TAS', 'NT', 'ACT'].map((s) => (
                                    <option key={s} value={s}>{s}</option>
                                ))}
                            </select>
                        </div>
                        <div className="w-32">
                            <label className="text-muted-foreground mb-1 block text-xs font-medium">Postcode</label>
                            <input
                                type="text"
                                value={postcode}
                                onChange={(e) => setPostcode(e.target.value)}
                                placeholder="3141"
                                className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>
                        <div className="flex items-end gap-2">
                            <button
                                onClick={() => handleFetch(false)}
                                disabled={isFetching || !suburb.trim()}
                                className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                            >
                                {isFetching ? <Loader2 className="h-4 w-4 animate-spin" /> : <Sparkles className="h-4 w-4" />}
                                Fetch Data
                            </button>
                            <button
                                onClick={() => handleFetch(true)}
                                disabled={isFetching || !suburb.trim()}
                                title="Force refresh"
                                className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-accent disabled:opacity-50"
                            >
                                <RefreshCw className="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    {error && <p className="mt-2 text-sm text-destructive">{error}</p>}
                </div>

                {/* Latest Result */}
                {result && (
                    <div className="rounded-lg border bg-card p-5">
                        <div className="mb-4 flex items-center gap-2">
                            <MapPin className="text-primary h-4 w-4" />
                            <h2 className="font-semibold">
                                {result.suburb_name}
                                {result.state && `, ${result.state}`}
                                {result.postcode && ` ${result.postcode}`}
                            </h2>
                            <span className="rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">
                                Source: {result.source}
                            </span>
                        </div>

                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                            {[
                                { label: 'Median House Price', value: formatCurrency(result.median_house_price) },
                                { label: 'Median Unit Price', value: formatCurrency(result.median_unit_price) },
                                { label: 'Weekly House Rent', value: result.median_rent_house ? `$${result.median_rent_house}/wk` : '—' },
                                { label: 'Weekly Unit Rent', value: result.median_rent_unit ? `$${result.median_rent_unit}/wk` : '—' },
                                { label: 'Rental Yield', value: result.rental_yield ? `${result.rental_yield}%` : '—' },
                            ].map((item) => (
                                <div key={item.label} className="rounded-md bg-muted/50 p-3">
                                    <p className="text-muted-foreground text-xs">{item.label}</p>
                                    <p className="mt-1 font-semibold">{item.value}</p>
                                </div>
                            ))}
                        </div>

                        {result.ai_insights?.market_summary && (
                            <div className="mt-4 rounded-md bg-primary/5 p-3">
                                <div className="flex items-center gap-2 mb-1">
                                    <TrendingUp className="text-primary h-4 w-4" />
                                    <span className="text-sm font-medium">Market Summary</span>
                                </div>
                                <p className="text-sm text-muted-foreground">{result.ai_insights.market_summary}</p>
                                {result.ai_insights.demand_level && (
                                    <span className={`mt-2 inline-block rounded-full px-2 py-0.5 text-xs ${
                                        result.ai_insights.demand_level === 'high' ? 'bg-green-100 text-green-700' :
                                        result.ai_insights.demand_level === 'medium' ? 'bg-yellow-100 text-yellow-700' :
                                        'bg-red-100 text-red-700'
                                    }`}>
                                        Demand: {result.ai_insights.demand_level}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                )}

                {/* Historical Data */}
                {suburb_data.data.length > 0 && (
                    <div className="rounded-lg border bg-card">
                        <div className="border-b px-5 py-3">
                            <h2 className="font-semibold">Cached Suburb Data</h2>
                        </div>
                        <div className="divide-y">
                            {suburb_data.data.map((row) => (
                                <div key={row.id} className="flex items-center justify-between px-5 py-3">
                                    <div className="flex items-center gap-2">
                                        <MapPin className="text-muted-foreground h-4 w-4" />
                                        <span className="font-medium">{row.suburb_name}</span>
                                        {row.state && <span className="text-muted-foreground text-sm">{row.state}</span>}
                                    </div>
                                    <div className="flex items-center gap-4 text-sm">
                                        <span>{formatCurrency(row.median_house_price)}</span>
                                        {row.rental_yield && (
                                            <span className="text-green-600">{row.rental_yield}% yield</span>
                                        )}
                                        {row.fetched_at && (
                                            <span className="text-muted-foreground">
                                                {new Date(row.fetched_at).toLocaleDateString()}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
