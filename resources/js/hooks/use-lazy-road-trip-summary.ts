import { JsonFetchError, laravelJsonFetch } from '@/lib/laravel-json-fetch';
import type { RoadTripSummaryByPeriod } from '@/pages/dashboard/types';
import { dashboard } from '@/routes';
import { useEffect, useState } from 'react';

type RoadTripSummaryResponse = {
    roadTripSummaryByPeriod: RoadTripSummaryByPeriod;
};

export function useLazyRoadTripSummary(options: {
    enabled: boolean;
    queryString: string;
}): {
    data: RoadTripSummaryByPeriod | null;
    loading: boolean;
    error: string | null;
} {
    const { enabled, queryString } = options;
    const [data, setData] = useState<RoadTripSummaryByPeriod | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        let cancelled = false;
        let idleId: number | undefined;
        let timeoutId: ReturnType<typeof setTimeout> | undefined;

        const runFetch = (): void => {
            void (async () => {
                setLoading(true);
                setError(null);
                try {
                    const base =
                        dashboard().url.split('?')[0] || dashboard().url || '';
                    const url = new URL(
                        `${base.replace(/\/$/, '')}/road-trip-summary`,
                        window.location.origin,
                    );
                    const qs = new URLSearchParams(
                        queryString.length > 0
                            ? queryString
                            : window.location.search.replace(/^\?/, ''),
                    );
                    qs.forEach((value, key) => {
                        url.searchParams.set(key, value);
                    });

                    const body =
                        await laravelJsonFetch<RoadTripSummaryResponse>(
                            url.toString(),
                            { method: 'GET' },
                        );
                    if (!cancelled) {
                        setData(body.roadTripSummaryByPeriod);
                    }
                } catch (e) {
                    if (!cancelled) {
                        setError(
                            e instanceof JsonFetchError
                                ? e.message
                                : e instanceof Error
                                  ? e.message
                                  : 'Failed to load road trip summary',
                        );
                        setData(null);
                    }
                } finally {
                    if (!cancelled) {
                        setLoading(false);
                    }
                }
            })();
        };

        const schedule = (): void => {
            if (typeof window.requestIdleCallback === 'function') {
                idleId = window.requestIdleCallback(() => runFetch(), {
                    timeout: 2000,
                });
            } else {
                timeoutId = setTimeout(runFetch, 100);
            }
        };

        schedule();

        return () => {
            cancelled = true;
            if (
                idleId !== undefined &&
                typeof window.cancelIdleCallback === 'function'
            ) {
                window.cancelIdleCallback(idleId);
            }
            if (timeoutId !== undefined) {
                clearTimeout(timeoutId);
            }
        };
    }, [enabled, queryString]);

    return { data, loading, error };
}
