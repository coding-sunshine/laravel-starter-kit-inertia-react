import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';

type TemplateOption = {
    id: number;
    name: string;
    check_type: string;
    checklist: Array<{ label?: string; result_type?: string }>;
};

interface Props {
    vehicles: { id: number; name: string }[];
    vehicleCheckTemplates: TemplateOption[];
    drivers: { id: number; name: string }[];
}

export default function DvirWizardIndex({
    vehicles,
    vehicleCheckTemplates,
    drivers,
}: Props) {
    const [step, setStep] = useState(1);
    const [checkType, setCheckType] = useState<'pre_trip' | 'post_trip'>(
        'pre_trip',
    );
    const [vehicleId, setVehicleId] = useState<number | ''>('');
    const [templateId, setTemplateId] = useState<number | ''>('');
    const [checklistResults, setChecklistResults] = useState<
        Record<number, { result?: string; value_text?: string; notes?: string }>
    >({});

    const templatesFiltered = vehicleCheckTemplates.filter(
        (t) => t.check_type === checkType,
    );
    const selectedTemplate = vehicleCheckTemplates.find(
        (t) => t.id === templateId,
    );
    const checklist = selectedTemplate?.checklist ?? [];
    const checklistArray = Array.isArray(checklist) ? checklist : [];

    const form = useForm({
        vehicle_id: vehicleId as number,
        vehicle_check_template_id: templateId as number,
        check_date: new Date().toISOString().slice(0, 10),
        performed_by_driver_id: '' as number | '',
        performed_by_user_id: '' as number | '',
        defect_id: '' as number | '',
        items: [] as Array<{
            item_index: number;
            label: string;
            result_type: string;
            result?: string;
            value_text?: string;
            notes?: string;
        }>,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Complete DVIR', href: '/fleet/dvir' },
    ];

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const items = checklistArray.map(
            (item: { label?: string; result_type?: string }, idx: number) => {
                const res = checklistResults[idx] ?? {};
                return {
                    item_index: idx,
                    label: item?.label ?? `Item ${idx + 1}`,
                    result_type: item?.result_type ?? 'pass_fail',
                    result: res.result ?? 'pass',
                    value_text: res.value_text ?? null,
                    notes: res.notes ?? null,
                };
            },
        );
        form.setData('vehicle_id', vehicleId as number);
        form.setData('vehicle_check_template_id', templateId as number);
        form.setData('items', items);
        form.post('/fleet/dvir', { forceFormData: false });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Complete DVIR" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/fleet">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Complete DVIR (Pre/Post Trip)
                    </h1>
                </div>

                <div className="flex gap-2 text-sm text-muted-foreground">
                    {[1, 2, 3, 4].map((s) => (
                        <span
                            key={s}
                            className={
                                step === s ? 'font-medium text-foreground' : ''
                            }
                        >
                            Step {s}{' '}
                        </span>
                    ))}
                </div>

                <form
                    onSubmit={handleSubmit}
                    className="max-w-xl space-y-4 rounded-lg border p-6"
                >
                    {step === 1 && (
                        <>
                            <div className="space-y-2">
                                <Label>Check type *</Label>
                                <select
                                    value={checkType}
                                    onChange={(e) =>
                                        setCheckType(
                                            e.target.value as
                                                | 'pre_trip'
                                                | 'post_trip',
                                        )
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    <option value="pre_trip">Pre-trip</option>
                                    <option value="post_trip">Post-trip</option>
                                </select>
                            </div>
                            <Button type="button" onClick={() => setStep(2)}>
                                Next
                            </Button>
                        </>
                    )}

                    {step === 2 && (
                        <>
                            <div className="space-y-2">
                                <Label>Vehicle *</Label>
                                <select
                                    required
                                    value={vehicleId}
                                    onChange={(e) =>
                                        setVehicleId(
                                            e.target.value
                                                ? Number(e.target.value)
                                                : '',
                                        )
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    <option value="">—</option>
                                    {vehicles.map((v) => (
                                        <option key={v.id} value={v.id}>
                                            {v.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setStep(1)}
                                >
                                    Back
                                </Button>
                                <Button
                                    type="button"
                                    onClick={() => setStep(3)}
                                    disabled={!vehicleId}
                                >
                                    Next
                                </Button>
                            </div>
                        </>
                    )}

                    {step === 3 && (
                        <>
                            <div className="space-y-2">
                                <Label>Template *</Label>
                                <select
                                    required
                                    value={templateId}
                                    onChange={(e) => {
                                        setTemplateId(
                                            e.target.value
                                                ? Number(e.target.value)
                                                : '',
                                        );
                                        setChecklistResults({});
                                    }}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    <option value="">—</option>
                                    {templatesFiltered.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-2">
                                <Label>Performed by (driver)</Label>
                                <select
                                    value={
                                        form.data.performed_by_driver_id || ''
                                    }
                                    onChange={(e) =>
                                        form.setData(
                                            'performed_by_driver_id',
                                            e.target.value
                                                ? Number(e.target.value)
                                                : '',
                                        )
                                    }
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                                >
                                    <option value="">—</option>
                                    {drivers.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setStep(2)}
                                >
                                    Back
                                </Button>
                                <Button
                                    type="button"
                                    onClick={() => setStep(4)}
                                    disabled={!templateId}
                                >
                                    Next
                                </Button>
                            </div>
                        </>
                    )}

                    {step === 4 && (
                        <>
                            <div className="space-y-2">
                                <Label>Check date *</Label>
                                <Input
                                    type="date"
                                    value={form.data.check_date}
                                    onChange={(e) =>
                                        form.setData(
                                            'check_date',
                                            e.target.value,
                                        )
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-3">
                                <Label>Checklist</Label>
                                {checklistArray.map(
                                    (
                                        item: {
                                            label?: string;
                                            result_type?: string;
                                        },
                                        idx: number,
                                    ) => (
                                        <div
                                            key={idx}
                                            className="rounded border p-3"
                                        >
                                            <div className="mb-2 font-medium">
                                                {item?.label ??
                                                    `Item ${idx + 1}`}
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                <select
                                                    value={
                                                        checklistResults[idx]
                                                            ?.result ?? 'pass'
                                                    }
                                                    onChange={(e) =>
                                                        setChecklistResults(
                                                            (prev) => ({
                                                                ...prev,
                                                                [idx]: {
                                                                    ...prev[
                                                                        idx
                                                                    ],
                                                                    result: e
                                                                        .target
                                                                        .value,
                                                                },
                                                            }),
                                                        )
                                                    }
                                                    className="h-9 rounded-md border border-input bg-transparent px-2 text-sm"
                                                >
                                                    <option value="pass">
                                                        Pass
                                                    </option>
                                                    <option value="fail">
                                                        Fail
                                                    </option>
                                                    <option value="na">
                                                        N/A
                                                    </option>
                                                </select>
                                                <Input
                                                    placeholder="Notes"
                                                    value={
                                                        checklistResults[idx]
                                                            ?.notes ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        setChecklistResults(
                                                            (prev) => ({
                                                                ...prev,
                                                                [idx]: {
                                                                    ...prev[
                                                                        idx
                                                                    ],
                                                                    notes: e
                                                                        .target
                                                                        .value,
                                                                },
                                                            }),
                                                        )
                                                    }
                                                    className="max-w-xs text-sm"
                                                />
                                            </div>
                                        </div>
                                    ),
                                )}
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setStep(3)}
                                >
                                    Back
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    Submit DVIR
                                </Button>
                            </div>
                        </>
                    )}
                </form>
            </div>
        </AppLayout>
    );
}
