import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useEffect } from 'react';

import { ActionCardStack } from '@/components/manager-brief/ActionCardStack';
import { LiveExposureTicker } from '@/components/manager-brief/LiveExposureTicker';
import { OperatorScoreboard } from '@/components/manager-brief/OperatorScoreboard';
import { PendingQueue } from '@/components/manager-brief/PendingQueue';
import { RefreshNowButton } from '@/components/manager-brief/RefreshNowButton';
import { StalenessBanner } from '@/components/manager-brief/StalenessBanner';
import { TrendStrip } from '@/components/manager-brief/TrendStrip';
import type { ManagerBriefPageProps } from '@/components/manager-brief/types';

const POLL_INTERVAL_MS = 15_000;

export default function ManagerBriefIndex({
    actions,
    generated_at,
    ai_status,
    live_exposure,
    operator_scoreboard,
    pending_queue,
    trend_strip,
    can_refresh,
    siding,
}: ManagerBriefPageProps) {
    // Polling for live widget data every 15 seconds — mirrors control-panel-v2 pattern
    useEffect(() => {
        const id = window.setInterval(() => {
            router.reload({
                only: [
                    'live_exposure',
                    'operator_scoreboard',
                    'pending_queue',
                    'trend_strip',
                    'widget_errors',
                ],
            });
        }, POLL_INTERVAL_MS);
        return () => window.clearInterval(id);
    }, []);

    return (
        <AppLayout>
            <Head title={`Manager Brief — ${siding.name}`} />

            <main className="mx-auto max-w-5xl px-4 py-6">
                {/* Page header */}
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">
                            Manager Brief
                        </h1>
                        <p className="text-sm text-slate-500">
                            {siding.name} ({siding.code}) — AI-powered shift
                            summary
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <StalenessBanner
                            generatedAt={generated_at}
                            aiStatus={ai_status}
                        />
                        <RefreshNowButton canRefresh={can_refresh} />
                    </div>
                </div>

                {/* Action cards — full width */}
                <section className="mb-6">
                    <ActionCardStack
                        actions={actions}
                        aiStatus={ai_status}
                    />
                </section>

                {/* Live widgets — 2×2 grid */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <LiveExposureTicker data={live_exposure} />
                    <OperatorScoreboard data={operator_scoreboard} />
                    <PendingQueue data={pending_queue} />
                    <TrendStrip data={trend_strip} />
                </div>
            </main>
        </AppLayout>
    );
}
