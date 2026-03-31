import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm, usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';

interface LoadingTimesFormProps {
    rakeId: number;
    loadingStart?: string | null;
    loadingEnd?: string | null;
    onTimesSaved?: (payload: {
        loading_start_time: string | null;
        loading_end_time: string | null;
    }) => void;
}

function getCsrfHeaders(): Record<string, string> {
    const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
    if (cookieMatch) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta?.getAttribute('content')) {
        return { 'X-CSRF-TOKEN': meta.getAttribute('content')! };
    }
    return {};
}

function toLocalInput(value?: string | null): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const pad = (n: number) => n.toString().padStart(2, '0');

    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1);
    const day = pad(date.getDate());
    const hours = pad(date.getHours());
    const minutes = pad(date.getMinutes());

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

export function LoadingTimesForm({
    rakeId,
    loadingStart,
    loadingEnd,
    onTimesSaved,
}: LoadingTimesFormProps) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };

    const { data, setData, processing, errors, put } = useForm({
        loading_start_time: toLocalInput(loadingStart),
        loading_end_time: toLocalInput(loadingEnd),
    });

    const [jsonSubmitting, setJsonSubmitting] = useState(false);
    const [fetchErrors, setFetchErrors] = useState<Record<string, string>>({});
    const [jsonSuccess, setJsonSuccess] = useState(false);

    const saveViaJson = useCallback(async (): Promise<void> => {
        setFetchErrors({});
        setJsonSuccess(false);
        setJsonSubmitting(true);
        try {
            const body = {
                loading_start_time: data.loading_start_time.trim() || null,
                loading_end_time: data.loading_end_time.trim() || null,
            };

            const response = await fetch(`/rakes/${rakeId}/loading-times`, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...getCsrfHeaders(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });

            const payload = (await response.json().catch(() => null)) as
                | {
                      loading_start_time?: string | null;
                      loading_end_time?: string | null;
                      message?: string;
                      errors?: Record<string, string[]>;
                  }
                | null;

            if (response.status === 422 && payload?.errors) {
                const next: Record<string, string> = {};
                for (const [key, msgs] of Object.entries(payload.errors)) {
                    if (Array.isArray(msgs) && msgs[0]) {
                        next[key] = msgs[0];
                    }
                }
                setFetchErrors(next);

                return;
            }

            if (!response.ok) {
                setFetchErrors({
                    loading_start_time: payload?.message ?? 'Save failed.',
                });

                return;
            }

            if (
                payload &&
                Object.prototype.hasOwnProperty.call(payload, 'loading_start_time') &&
                Object.prototype.hasOwnProperty.call(payload, 'loading_end_time')
            ) {
                const startIso = payload.loading_start_time ?? null;
                const endIso = payload.loading_end_time ?? null;
                onTimesSaved?.({
                    loading_start_time: startIso,
                    loading_end_time: endIso,
                });
                setData({
                    loading_start_time: toLocalInput(startIso),
                    loading_end_time: toLocalInput(endIso),
                });
                setJsonSuccess(true);
            }
        } finally {
            setJsonSubmitting(false);
        }
    }, [data.loading_end_time, data.loading_start_time, onTimesSaved, rakeId, setData]);

    const isBusy = processing || jsonSubmitting;
    const startError = errors.loading_start_time ?? fetchErrors.loading_start_time;
    const endError = errors.loading_end_time ?? fetchErrors.loading_end_time;

    const hasSavedTimes = Boolean(data.loading_start_time || data.loading_end_time);
    const startDisplay = data.loading_start_time
        ? data.loading_start_time.replace('T', ' ')
        : null;
    const endDisplay = data.loading_end_time ? data.loading_end_time.replace('T', ' ') : null;

    const showFlashSaved = flash?.success === 'Loading times updated.';
    const showSavedMessage = showFlashSaved || jsonSuccess;

    return (
        <div className="rounded-md border bg-card p-3 space-y-3">
            <p className="text-xs font-medium text-muted-foreground">Loading time</p>
            {hasSavedTimes && (
                <p className="text-[0.7rem] text-muted-foreground">
                    Last saved: {startDisplay}
                    {endDisplay ? ` → ${endDisplay}` : ''}
                </p>
            )}
            <div className="grid gap-3 md:grid-cols-2">
                <div className="space-y-1">
                    <Label htmlFor="loading_start_time">Loading start time</Label>
                    <Input
                        id="loading_start_time"
                        type="datetime-local"
                        value={data.loading_start_time}
                        onChange={(e) => {
                            setJsonSuccess(false);
                            setData('loading_start_time', e.target.value);
                        }}
                    />
                    <InputError message={startError} />
                </div>
                <div className="space-y-1">
                    <Label htmlFor="loading_end_time">Loading end time</Label>
                    <Input
                        id="loading_end_time"
                        type="datetime-local"
                        value={data.loading_end_time}
                        onChange={(e) => {
                            setJsonSuccess(false);
                            setData('loading_end_time', e.target.value);
                        }}
                    />
                    <InputError message={endError} />
                </div>
            </div>
            <div className="pt-1">
                <Button
                    type="button"
                    size="sm"
                    disabled={isBusy}
                    onClick={() =>
                        onTimesSaved !== undefined
                            ? void saveViaJson()
                            : put(`/rakes/${rakeId}/loading-times`, { preserveScroll: true })
                    }
                >
                    Save loading times
                </Button>
                {showSavedMessage && !startError && !endError && (
                    <span className="ml-3 text-xs text-emerald-600">Saved.</span>
                )}
            </div>
        </div>
    );
}
