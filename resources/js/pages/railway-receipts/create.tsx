import InputError from '@/components/input-error';
import { RrPredictionsPanel } from '@/components/railway-receipts/rr-predictions-panel';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';

interface Rake {
    id: number;
    rake_number: string;
    siding_id: number;
}

interface Siding {
    id: number;
    name: string;
    code: string;
}

type RrPrediction = {
    rake_id: number;
    rake_number: string | null;
    predicted_weight_mt: number;
    predicted_rr_date: string | null;
    prediction_confidence: number;
    prediction_status: string;
    variance_percent: number | null;
};

interface Props {
    rakes: Rake[];
    sidings: Siding[];
    preselectedRakeId: number | null;
    rrPredictions: RrPrediction[];
}

export default function RailwayReceiptsCreate({
    rakes,
    preselectedRakeId,
    rrPredictions,
}: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Railway Receipts', href: '/railway-receipts' },
        { title: 'Add RR document', href: '/railway-receipts/create' },
    ];

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = new FormData(form);
        router.post('/railway-receipts', formData, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add RR document" />
            <div className="space-y-6">
                <h2 className="text-lg font-medium">Add RR document</h2>
                <RrPredictionsPanel rrPredictions={rrPredictions} />
                <form
                    onSubmit={handleSubmit}
                    className="max-w-md space-y-4"
                    encType="multipart/form-data"
                >
                    <div className="grid gap-2">
                        <Label htmlFor="rake_id">Rake *</Label>
                        <select
                            id="rake_id"
                            name="rake_id"
                            required
                            defaultValue={preselectedRakeId ?? ''}
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            <option value="">Select rake</option>
                            {rakes.map((r) => (
                                <option key={r.id} value={r.id}>
                                    {r.rake_number}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors?.rake_id} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="rr_number">RR number *</Label>
                        <Input
                            id="rr_number"
                            name="rr_number"
                            required
                            className="text-sm"
                        />
                        <InputError message={errors?.rr_number} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="rr_received_date">
                            RR received date *
                        </Label>
                        <Input
                            id="rr_received_date"
                            name="rr_received_date"
                            type="date"
                            required
                            className="text-sm"
                        />
                        <InputError message={errors?.rr_received_date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="rr_weight_mt">Weight (MT)</Label>
                        <Input
                            id="rr_weight_mt"
                            name="rr_weight_mt"
                            type="number"
                            step="0.01"
                            min="0"
                            className="text-sm"
                        />
                        <InputError message={errors?.rr_weight_mt} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="fnr">FNR (optional)</Label>
                        <Input id="fnr" name="fnr" className="text-sm" />
                        <InputError message={errors?.fnr} />
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                        <div className="grid gap-2">
                            <Label htmlFor="from_station_code">
                                From station code
                            </Label>
                            <Input
                                id="from_station_code"
                                name="from_station_code"
                                className="text-sm"
                            />
                            <InputError message={errors?.from_station_code} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="to_station_code">
                                To station code
                            </Label>
                            <Input
                                id="to_station_code"
                                name="to_station_code"
                                className="text-sm"
                            />
                            <InputError message={errors?.to_station_code} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="freight_total">Freight total (₹)</Label>
                        <Input
                            id="freight_total"
                            name="freight_total"
                            type="number"
                            step="0.01"
                            min="0"
                            className="text-sm"
                        />
                        <InputError message={errors?.freight_total} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="document_status">Status</Label>
                        <select
                            id="document_status"
                            name="document_status"
                            className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                        >
                            <option value="received">Received</option>
                            <option value="verified">Verified</option>
                            <option value="discrepancy">Discrepancy</option>
                        </select>
                        <InputError message={errors?.document_status} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="pdf">PDF (optional)</Label>
                        <Input
                            id="pdf"
                            name="pdf"
                            type="file"
                            accept=".pdf"
                            className="text-sm"
                        />
                        <InputError message={errors?.pdf} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit">Save</Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.get('/railway-receipts')}
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
