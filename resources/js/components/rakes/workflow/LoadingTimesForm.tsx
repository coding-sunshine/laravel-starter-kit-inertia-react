import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm, usePage } from '@inertiajs/react';

interface LoadingTimesFormProps {
    rakeId: number;
    loadingStart?: string | null;
    loadingEnd?: string | null;
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
}: LoadingTimesFormProps) {
    const { flash } = usePage().props as { flash?: { success?: string; error?: string } };

    const { data, setData, processing, errors, put } = useForm({
        loading_start_time: toLocalInput(loadingStart),
        loading_end_time: toLocalInput(loadingEnd),
    });

    const hasSavedTimes = Boolean(data.loading_start_time || data.loading_end_time);
    const startDisplay = data.loading_start_time
        ? data.loading_start_time.replace('T', ' ')
        : null;
    const endDisplay = data.loading_end_time ? data.loading_end_time.replace('T', ' ') : null;

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
                        onChange={(e) => setData('loading_start_time', e.target.value)}
                    />
                    <InputError message={errors.loading_start_time} />
                </div>
                <div className="space-y-1">
                    <Label htmlFor="loading_end_time">Loading end time</Label>
                    <Input
                        id="loading_end_time"
                        type="datetime-local"
                        value={data.loading_end_time}
                        onChange={(e) => setData('loading_end_time', e.target.value)}
                    />
                    <InputError message={errors.loading_end_time} />
                </div>
            </div>
            <div className="pt-1">
                <Button
                    type="button"
                    size="sm"
                    disabled={processing}
                    onClick={() => put(`/rakes/${rakeId}/loading-times`, { preserveScroll: true })}
                >
                    Save loading times
                </Button>
                {flash?.success === 'Loading times updated.' && (
                    <span className="ml-3 text-xs text-emerald-600">Saved.</span>
                )}
            </div>
        </div>
    );
}

