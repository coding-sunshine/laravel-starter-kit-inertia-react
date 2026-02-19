import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';

interface Rake {
    id: number;
    rake_number: string;
    siding?: { id: number; name: string; code: string };
}

interface RrDocument {
    id: number;
    rake_id: number;
    rr_number: string;
    rr_received_date: string;
    rr_weight_mt: string | null;
    document_status: string;
    has_discrepancy: boolean;
    discrepancy_details: string | null;
    rake?: Rake;
}

interface Props {
    rrDocument: RrDocument;
}

export default function RailwayReceiptsShow({ rrDocument }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Railway Receipts', href: '/railway-receipts' },
        { title: rrDocument.rr_number, href: `/railway-receipts/${rrDocument.id}` },
    ];

    const form = useForm({
        rr_number: rrDocument.rr_number,
        rr_received_date: rrDocument.rr_received_date.split('T')[0],
        rr_weight_mt: rrDocument.rr_weight_mt ?? '',
        document_status: rrDocument.document_status,
        has_discrepancy: rrDocument.has_discrepancy,
        discrepancy_details: rrDocument.discrepancy_details ?? '',
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/railway-receipts/${rrDocument.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`RR ${rrDocument.rr_number}`} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-medium">RR {rrDocument.rr_number}</h2>
                    <Button variant="outline" onClick={() => router.get('/railway-receipts')}>
                        Back to list
                    </Button>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                        <CardDescription>
                            Rake: {rrDocument.rake?.rake_number} — {rrDocument.rake?.siding?.name}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <dl className="grid gap-2 text-sm">
                            <dt className="font-medium">RR number</dt>
                            <dd>{rrDocument.rr_number}</dd>
                            <dt className="font-medium">Received date</dt>
                            <dd>{rrDocument.rr_received_date}</dd>
                            <dt className="font-medium">Weight (MT)</dt>
                            <dd>{rrDocument.rr_weight_mt ?? '-'}</dd>
                            <dt className="font-medium">Status</dt>
                            <dd>{rrDocument.document_status}</dd>
                            {rrDocument.has_discrepancy && rrDocument.discrepancy_details && (
                                <>
                                    <dt className="font-medium">Discrepancy details</dt>
                                    <dd>{rrDocument.discrepancy_details}</dd>
                                </>
                            )}
                        </dl>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Edit</CardTitle>
                        <CardDescription>Update RR fields</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleUpdate} className="max-w-md space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="rr_number">RR number *</Label>
                                <Input
                                    id="rr_number"
                                    value={form.data.rr_number}
                                    onChange={(e) => form.setData('rr_number', e.target.value)}
                                    className="text-sm"
                                />
                                <InputError message={form.errors.rr_number ?? errors?.rr_number} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="rr_received_date">RR received date *</Label>
                                <Input
                                    id="rr_received_date"
                                    type="date"
                                    value={form.data.rr_received_date}
                                    onChange={(e) => form.setData('rr_received_date', e.target.value)}
                                    className="text-sm"
                                />
                                <InputError message={form.errors.rr_received_date ?? errors?.rr_received_date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="rr_weight_mt">Weight (MT)</Label>
                                <Input
                                    id="rr_weight_mt"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={form.data.rr_weight_mt}
                                    onChange={(e) => form.setData('rr_weight_mt', e.target.value)}
                                    className="text-sm"
                                />
                                <InputError message={form.errors.rr_weight_mt ?? errors?.rr_weight_mt} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="document_status">Status</Label>
                                <select
                                    id="document_status"
                                    value={form.data.document_status}
                                    onChange={(e) => form.setData('document_status', e.target.value)}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                >
                                    <option value="received">Received</option>
                                    <option value="verified">Verified</option>
                                    <option value="discrepancy">Discrepancy</option>
                                </select>
                                <InputError message={form.errors.document_status ?? errors?.document_status} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="discrepancy_details">Discrepancy details</Label>
                                <textarea
                                    id="discrepancy_details"
                                    rows={3}
                                    value={form.data.discrepancy_details}
                                    onChange={(e) => form.setData('discrepancy_details', e.target.value)}
                                    className="rounded-md border border-input bg-background px-4 py-2.5 text-sm"
                                />
                                <InputError message={form.errors.discrepancy_details ?? errors?.discrepancy_details} />
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" disabled={form.processing}>
                                    Update
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
