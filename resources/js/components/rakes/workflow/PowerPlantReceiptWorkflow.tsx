import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle, Clock, FileText, Trash2, Upload } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface PowerPlant {
    id: number;
    name: string;
    code: string;
}

interface PowerPlantReceipt {
    id: number;
    power_plant_id: number;
    receipt_date: string | null;
    weight_mt: string | number;
    rr_reference: string | null;
    status: string;
    file_url: string | null;
    file_name?: string | null;
    powerPlant?: { id: number; name: string; code: string } | null;
}

interface PowerPlantReceiptWorkflowProps {
    rake: {
        id: number;
        is_diverted?: boolean;
        destination_code?: string | null;
        powerPlantReceipts?: PowerPlantReceipt[];
    };
    powerPlants: PowerPlant[];
    disabled: boolean;
}

function ReceiptSlotCard({
    rakeId,
    plant,
    receipts,
    disabled,
    errors,
    onDeleteReceipt,
}: {
    rakeId: number;
    plant: PowerPlant;
    receipts: PowerPlantReceipt[];
    disabled: boolean;
    errors?: Record<string, string>;
    onDeleteReceipt: (receiptId: number) => void;
}) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);

    const form = useForm({
        power_plant_id: String(plant.id),
        receipt_date: '',
        status: 'pending',
        weight_mt: '',
        rr_reference: '',
        receipt_pdf: null as File | null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        form.post(`/rakes/${rakeId}/power-plant-receipts`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setSelectedFile(null);
            },
        });
    };

    const hasReceipts = receipts.length > 0;

    return (
        <div className="rounded-lg border bg-muted/30 p-4 space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p className="text-sm font-medium">
                        {plant.name} ({plant.code})
                    </p>
                    <p className="text-xs text-muted-foreground mt-0.5">
                        Upload one or more receipts for this destination.
                    </p>
                </div>
                <Badge variant={hasReceipts ? 'default' : 'secondary'}>
                    {hasReceipts ? 'Uploaded' : 'Pending'}
                </Badge>
            </div>

            {!disabled && !hasReceipts && (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-2 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`receipt_date_${plant.id}`}>Receipt date *</Label>
                            <Input
                                id={`receipt_date_${plant.id}`}
                                type="date"
                                required
                                value={form.data.receipt_date}
                                onChange={(e) =>
                                    form.setData('receipt_date', e.target.value)
                                }
                            />
                            <InputError message={errors?.receipt_date ?? form.errors.receipt_date} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`status_${plant.id}`}>Status *</Label>
                            <select
                                id={`status_${plant.id}`}
                                required
                                value={form.data.status}
                                onChange={(e) => form.setData('status', e.target.value)}
                                className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                            >
                                <option value="pending">Pending</option>
                                <option value="reached">Reached</option>
                            </select>
                            <InputError message={errors?.status ?? form.errors.status} />
                        </div>
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor={`weight_mt_${plant.id}`}>Receipt weight (MT) *</Label>
                            <Input
                                id={`weight_mt_${plant.id}`}
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                value={form.data.weight_mt}
                                onChange={(e) => form.setData('weight_mt', e.target.value)}
                            />
                            <InputError message={errors?.weight_mt ?? form.errors.weight_mt} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor={`rr_reference_${plant.id}`}>RR number</Label>
                            <Input
                                id={`rr_reference_${plant.id}`}
                                value={form.data.rr_reference}
                                onChange={(e) =>
                                    form.setData('rr_reference', e.target.value)
                                }
                            />
                            <InputError message={errors?.rr_reference ?? form.errors.rr_reference} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`receipt_pdf_${plant.id}`}>Receipt PDF *</Label>
                        <Input
                            id={`receipt_pdf_${plant.id}`}
                            type="file"
                            accept=".pdf"
                            required
                            onChange={(e) => {
                                const file = e.target.files?.[0] ?? null;
                                setSelectedFile(file);
                                form.setData('receipt_pdf', file);
                            }}
                            className="file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        />
                        {selectedFile && (
                            <p className="text-xs text-muted-foreground">
                                Selected: <span className="font-medium">{selectedFile.name}</span>
                            </p>
                        )}
                        <InputError message={errors?.receipt_pdf ?? form.errors.receipt_pdf} />
                        <InputError message={errors?.power_plant_id ?? form.errors.power_plant_id} />
                    </div>

                    <div className="flex justify-end">
                        <Button
                            type="submit"
                            disabled={form.processing || !form.data.receipt_pdf}
                            data-pan="rake-powerplantreceipt-upload-button"
                        >
                            <Upload className="mr-2 h-4 w-4" />
                            {form.processing ? 'Saving…' : 'Save receipt'}
                        </Button>
                    </div>
                </form>
            )}

            {hasReceipts && (
                <div className="space-y-2">
                    <p className="text-xs font-medium text-muted-foreground">Saved receipts</p>
                    {receipts.map((r) => (
                        <div
                            key={r.id}
                            className="flex flex-col gap-2 rounded-md border bg-background p-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="space-y-1">
                                <div className="text-xs text-muted-foreground">
                                    Date: {r.receipt_date ?? '-'} · Weight: {r.weight_mt} MT · Status: {r.status}
                                    {r.rr_reference ? ` · RR: ${r.rr_reference}` : ''}
                                </div>
                                {r.file_url && (
                                    <a
                                        href={r.file_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-xs font-medium underline underline-offset-4"
                                    >
                                        {r.file_name ?? 'View PDF'}
                                    </a>
                                )}
                            </div>
                            {!disabled && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => onDeleteReceipt(r.id)}
                                    data-pan="rake-powerplantreceipt-delete-button"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Delete
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {!disabled && hasReceipts && (
                <div className="rounded-md border border-dashed bg-background px-3 py-2 text-xs text-muted-foreground">
                    A receipt already exists for this power plant. Delete it to upload again.
                </div>
            )}
        </div>
    );
}

export function PowerPlantReceiptWorkflow({
    rake,
    powerPlants,
    disabled,
}: PowerPlantReceiptWorkflowProps) {
    const {
        props: { errors },
    } = usePage<{ errors?: Record<string, string> }>();

    const isDiverted = Boolean(rake.is_diverted);
    const receipts = rake.powerPlantReceipts ?? [];
    const [diversionModeBusy, setDiversionModeBusy] = useState(false);
    const [destinationPlantIds, setDestinationPlantIds] = useState<number[]>([]);
    const [newDestinationId, setNewDestinationId] = useState<string>('');

    const resolvedPowerPlant = useMemo(() => {
        const code = rake.destination_code ?? null;
        if (!code) return null;
        return powerPlants.find((pp) => pp.code === code) ?? null;
    }, [powerPlants, rake.destination_code]);

    useEffect(() => {
        if (!isDiverted) {
            return;
        }

        const fromReceipts = Array.from(
            new Set((receipts ?? []).map((r) => Number(r.power_plant_id)).filter((id) => Number.isFinite(id) && id > 0)),
        );

        setDestinationPlantIds((prev) => {
            const merged = Array.from(new Set([...prev, ...fromReceipts]));
            return merged;
        });
    }, [isDiverted, receipts]);

    const handleDivertedToggle = (checked: boolean) => {
        setDiversionModeBusy(true);
        router.patch(
            `/rakes/${rake.id}/diversion-mode`,
            { is_diverted: checked },
            {
                preserveScroll: true,
                onFinish: () => setDiversionModeBusy(false),
            },
        );
    };

    const deleteReceipt = (receiptId: number) => {
        if (!window.confirm('Delete this power plant receipt?')) {
            return;
        }

        router.delete(`/rakes/${rake.id}/power-plant-receipts/${receiptId}`, {
            preserveScroll: true,
        });
    };

    const hasReceipts = receipts.length > 0;
    const getStatusIcon = () => {
        if (!hasReceipts) return <Clock className="h-4 w-4" />;
        return <CheckCircle className="h-4 w-4 text-green-600" />;
    };
    const getStatusText = () => {
        if (!hasReceipts) return 'Pending';
        return 'Recorded';
    };

    const receiptsByPlantId = useMemo(() => {
        const map = new Map<number, PowerPlantReceipt[]>();
        for (const r of receipts) {
            const id = Number(r.power_plant_id);
            if (!Number.isFinite(id)) {
                continue;
            }
            const current = map.get(id) ?? [];
            current.push(r);
            map.set(id, current);
        }
        return map;
    }, [receipts]);

    const destinationPlants = useMemo(() => {
        return destinationPlantIds
            .map((id) => powerPlants.find((pp) => pp.id === id))
            .filter((pp): pp is PowerPlant => Boolean(pp));
    }, [destinationPlantIds, powerPlants]);

    const addDestination = () => {
        const id = Number(newDestinationId);
        if (!Number.isFinite(id) || id <= 0) {
            return;
        }

        setDestinationPlantIds((prev) => Array.from(new Set([...prev, id])));
        setNewDestinationId('');
    };

    const removeDestination = (plantId: number) => {
        const hasAnyReceipts = (receiptsByPlantId.get(plantId) ?? []).length > 0;
        if (hasAnyReceipts) {
            alert('Cannot remove this destination because receipts already exist for it.');
            return;
        }

        setDestinationPlantIds((prev) => prev.filter((id) => id !== plantId));
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5" />
                        Power Plant Receipt
                    </div>
                    <div className="flex items-center gap-2">
                        {getStatusIcon()}
                        <Badge variant={hasReceipts ? 'default' : 'secondary'}>
                            {getStatusText()}
                        </Badge>
                    </div>
                </CardTitle>
                <CardDescription>
                    Record receipt details and upload the receipt PDF.
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-6">
                {!disabled && (
                    <div className="flex flex-col gap-3 rounded-lg border border-dashed p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="space-y-1">
                            <p className="text-sm font-medium">Diverted rake (multiple power plants)</p>
                            <p className="text-xs text-muted-foreground">
                                Enable when this rake is diverted and you need receipts for multiple destinations.
                            </p>
                        </div>
                        <label className="flex cursor-pointer items-center gap-3">
                            <Checkbox
                                checked={isDiverted}
                                disabled={diversionModeBusy}
                                onCheckedChange={(v) => handleDivertedToggle(v === true)}
                                data-pan="rake-powerplantreceipt-diverted-mode-checkbox"
                            />
                            <span className="text-sm font-medium">{isDiverted ? 'Enabled' : 'Disabled'}</span>
                        </label>
                    </div>
                )}

                {!isDiverted && (
                    <div className="rounded-lg border bg-muted/30 p-4">
                        <p className="text-sm font-medium">Power plant (from destination code)</p>
                        <p className="text-xs text-muted-foreground mt-1">
                            Destination code: <span className="font-mono">{rake.destination_code ?? '-'}</span>
                        </p>
                        <p className="mt-2 text-sm">
                            {resolvedPowerPlant
                                ? `${resolvedPowerPlant.name} (${resolvedPowerPlant.code})`
                                : 'Not found in master data. Enable diverted mode to select a power plant.'}
                        </p>
                    </div>
                )}

                {isDiverted && (
                    <div className="space-y-4">
                        {!disabled && (
                            <div className="rounded-lg border border-dashed p-4 space-y-3">
                                <p className="text-sm font-medium">Diversion destinations (power plants)</p>
                                <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
                                    <div className="flex-1 grid gap-2">
                                        <Label htmlFor="new_destination_power_plant">
                                            Add destination power plant
                                        </Label>
                                        <select
                                            id="new_destination_power_plant"
                                            value={newDestinationId}
                                            onChange={(e) => setNewDestinationId(e.target.value)}
                                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                        >
                                            <option value="">Select power plant</option>
                                            {powerPlants
                                                .filter((pp) => !destinationPlantIds.includes(pp.id))
                                                .map((pp) => (
                                                    <option key={pp.id} value={pp.id}>
                                                        {pp.name} ({pp.code})
                                                    </option>
                                                ))}
                                        </select>
                                    </div>
                                    <Button
                                        type="button"
                                        onClick={addDestination}
                                        disabled={!newDestinationId}
                                        data-pan="rake-powerplantreceipt-destination-add"
                                    >
                                        Add destination
                                    </Button>
                                </div>
                            </div>
                        )}

                        {destinationPlants.length === 0 ? (
                            <div className="text-sm text-muted-foreground">
                                Add at least one destination power plant to upload receipts.
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {destinationPlants.map((plant) => {
                                    const slotReceipts =
                                        receiptsByPlantId.get(plant.id) ?? [];

                                    return (
                                        <div key={plant.id} className="space-y-2">
                                            {!disabled && slotReceipts.length === 0 && (
                                                <div className="flex justify-end">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => removeDestination(plant.id)}
                                                        data-pan="rake-powerplantreceipt-destination-remove"
                                                    >
                                                        Remove destination
                                                    </Button>
                                                </div>
                                            )}
                                            <ReceiptSlotCard
                                                rakeId={rake.id}
                                                plant={plant}
                                                receipts={slotReceipts}
                                                disabled={disabled}
                                                errors={errors}
                                                onDeleteReceipt={deleteReceipt}
                                            />
                                        </div>
                                    );
                                })}
                            </div>
                        )}

                        <InputError message={errors?.power_plant_id} />
                    </div>
                )}

                {!isDiverted && !disabled && resolvedPowerPlant && (
                    <ReceiptSlotCard
                        rakeId={rake.id}
                        plant={resolvedPowerPlant}
                        receipts={receiptsByPlantId.get(resolvedPowerPlant.id) ?? []}
                        disabled={false}
                        errors={errors}
                        onDeleteReceipt={deleteReceipt}
                    />
                )}

                {!isDiverted && !disabled && !resolvedPowerPlant && (
                    <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                        Destination power plant could not be resolved from destination code. Enable diverted mode to
                        select a power plant and upload receipts.
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

