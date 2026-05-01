import { Head, router } from '@inertiajs/react';

type Rake = {
    id: number;
    rake_number: string;
    placement_time: string | null;
    loading_end_time: string | null;
};

type Props = {
    siding: { id: number; name: string };
    rakes: Rake[];
};

export default function QuickPlacement({ siding, rakes }: Props) {
    const submit = (rakeId: number, event: 'placed' | 'released') => {
        router.post(
            `/sidings/${siding.id}/quick-placement`,
            { rake_id: rakeId, event },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Quick placement — ${siding.name}`} />
            <div className="mx-auto max-w-md p-4">
                <h1 className="mb-4 text-xl font-semibold">{siding.name}</h1>
                <p className="text-muted-foreground mb-4 text-sm">
                    Tap once when a rake is placed, again when released. Server stamps the time.
                </p>
                <ul className="space-y-3">
                    {rakes.map((r) => (
                        <li key={r.id} className="rounded-lg border p-3">
                            <div className="font-medium">{r.rake_number}</div>
                            <div className="text-muted-foreground mt-1 text-xs">
                                {r.placement_time
                                    ? `Placed ${new Date(r.placement_time).toLocaleString()}`
                                    : 'Not placed'}
                                {r.loading_end_time
                                    ? ` · Released ${new Date(r.loading_end_time).toLocaleString()}`
                                    : ''}
                            </div>
                            <div className="mt-3 flex gap-2">
                                <button
                                    type="button"
                                    className="bg-primary text-primary-foreground rounded-md px-4 py-2 text-sm disabled:opacity-50"
                                    disabled={r.placement_time !== null}
                                    onClick={() => submit(r.id, 'placed')}
                                    data-pan="sidings-quick-placement-placed"
                                >
                                    Placed
                                </button>
                                <button
                                    type="button"
                                    className="rounded-md border px-4 py-2 text-sm disabled:opacity-50"
                                    disabled={r.placement_time === null || r.loading_end_time !== null}
                                    onClick={() => submit(r.id, 'released')}
                                    data-pan="sidings-quick-placement-released"
                                >
                                    Released
                                </button>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </>
    );
}
