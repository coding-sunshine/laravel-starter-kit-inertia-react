import { AlertTriangle, MapPin, RefreshCw, Train as TrainIcon } from 'lucide-react';
import { LiveClock } from './LiveClock';

interface Props {
    totalSidings: number;
    rakesRunning: number;
    activeAlerts: number;
    autoRefresh: boolean;
    onToggleAutoRefresh: () => void;
    lastUpdatedAt: string | null;
    view?: 'card' | 'table';
    onChangeView?: (v: 'card' | 'table') => void;
    subtitle?: string;
}

export function PersistentHeader({
    totalSidings,
    rakesRunning,
    activeAlerts,
    autoRefresh,
    onToggleAutoRefresh,
    lastUpdatedAt,
    view,
    onChangeView,
    subtitle,
}: Props) {
    const lastUpdatedLabel = lastUpdatedAt
        ? new Date(lastUpdatedAt).toLocaleTimeString('en-IN', {
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit',
              hour12: false,
          })
        : '—';

    return (
        <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div className="mx-auto flex max-w-[1600px] flex-wrap items-center gap-4 px-4 py-3">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                        <TrainIcon className="h-5 w-5" aria-hidden />
                    </div>
                    <div>
                        <div className="text-sm font-semibold leading-tight text-slate-900">
                            SHAReports
                        </div>
                        <div className="text-xs leading-tight text-slate-500">
                            {subtitle ?? 'Multi-Siding Monitoring Dashboard'}
                        </div>
                    </div>
                </div>

                <div className="ml-auto flex flex-wrap items-center gap-3">
                    <HeaderKpi
                        icon={<TrainIcon className="h-4 w-4" aria-hidden />}
                        label="Total Sidings"
                        value={totalSidings}
                        tone="sky"
                    />
                    <HeaderKpi
                        icon={<MapPin className="h-4 w-4" aria-hidden />}
                        label="Rakes Running"
                        value={rakesRunning}
                        tone="emerald"
                    />
                    <HeaderKpi
                        icon={<AlertTriangle className="h-4 w-4" aria-hidden />}
                        label="Active Alerts"
                        value={activeAlerts}
                        tone={activeAlerts > 0 ? 'rose' : 'slate'}
                    />

                    <div className="hidden h-10 w-px bg-slate-200 md:block" />

                    <LiveClock className="text-right" />

                    <div className="hidden h-10 w-px bg-slate-200 md:block" />

                    <AutoRefreshControl
                        active={autoRefresh}
                        onToggle={onToggleAutoRefresh}
                        lastUpdatedLabel={lastUpdatedLabel}
                    />

                    {view && onChangeView && (
                        <ViewSwitcher value={view} onChange={onChangeView} />
                    )}
                </div>
            </div>
        </header>
    );
}

function HeaderKpi({
    icon,
    label,
    value,
    tone,
}: {
    icon: React.ReactNode;
    label: string;
    value: number;
    tone: 'sky' | 'emerald' | 'rose' | 'slate';
}) {
    const tones: Record<typeof tone, string> = {
        sky: 'bg-sky-50 text-sky-700 ring-sky-200',
        emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        rose: 'bg-rose-50 text-rose-700 ring-rose-200',
        slate: 'bg-slate-50 text-slate-700 ring-slate-200',
    };

    return (
        <div
            className={`flex items-center gap-2 rounded-lg px-3 py-1.5 ring-1 ring-inset ${tones[tone]}`}
        >
            <span aria-hidden>{icon}</span>
            <div>
                <div className="text-[11px] font-medium uppercase tracking-wide leading-none opacity-75">
                    {label}
                </div>
                <div className="text-base font-semibold leading-tight tabular-nums">
                    {value}
                </div>
            </div>
        </div>
    );
}

function AutoRefreshControl({
    active,
    onToggle,
    lastUpdatedLabel,
}: {
    active: boolean;
    onToggle: () => void;
    lastUpdatedLabel: string;
}) {
    return (
        <div className="flex items-center gap-2 text-xs text-slate-600">
            <button
                type="button"
                onClick={onToggle}
                aria-pressed={active}
                className={`group inline-flex items-center gap-2 rounded-full border px-3 py-1.5 font-medium transition ${
                    active
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                        : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                }`}
            >
                <RefreshCw
                    className={`h-3.5 w-3.5 ${active ? 'animate-spin-slow' : ''}`}
                    aria-hidden
                />
                Auto Refresh
                <span
                    className={`h-2 w-2 rounded-full ${active ? 'bg-emerald-500' : 'bg-slate-300'}`}
                    aria-hidden
                />
            </button>
            <span className="hidden tabular-nums sm:inline">
                Last updated {lastUpdatedLabel}
            </span>
        </div>
    );
}

function ViewSwitcher({
    value,
    onChange,
}: {
    value: 'card' | 'table';
    onChange: (v: 'card' | 'table') => void;
}) {
    return (
        <div className="inline-flex overflow-hidden rounded-md border border-slate-300 text-xs">
            {(['card', 'table'] as const).map((v) => (
                <button
                    key={v}
                    type="button"
                    onClick={() => onChange(v)}
                    aria-pressed={value === v}
                    className={`px-3 py-1.5 font-medium capitalize transition ${
                        value === v
                            ? 'bg-sky-600 text-white'
                            : 'bg-white text-slate-600 hover:bg-slate-50'
                    }`}
                >
                    {v} View
                </button>
            ))}
        </div>
    );
}
