import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/input-error';
import { Plus, Pencil, Trash2 } from 'lucide-react';

export type ProductionType = 'coal' | 'ob';

interface ProductionEntry {
    id: number;
    type: string;
    date: string;
    trip: string;
    qty: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    entries: ProductionEntry[];
    type: ProductionType;
}

function productionBasePath(type: ProductionType): string {
    return type === 'coal' ? '/production/coal' : '/production/ob';
}

function productionTitle(type: ProductionType): string {
    return type === 'coal' ? 'Production – Coal' : 'Production – OB';
}

function formatDateOnly(value: string): string {
    if (!value) return '';
    return value.slice(0, 10);
}

export default function ProductionIndex({ entries, type }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const [showForm, setShowForm] = useState(false);
    const [date, setDate] = useState('');
    const [trip, setTrip] = useState('');
    const [qty, setQty] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const basePath = productionBasePath(type);
    const title = productionTitle(type);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title, href: basePath },
    ];

    const handleCreate = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(basePath, { date, trip, qty }, {
            preserveScroll: true,
            onFinish: () => {
                setIsSubmitting(false);
                setShowForm(false);
                setDate('');
                setTrip('');
                setQty('');
            },
        });
    };

    const handleDelete = (id: number) => {
        if (!confirm('Delete this entry?')) return;
        router.delete(`${basePath}/${id}`, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">{title}</h1>
                    <Button
                        type="button"
                        onClick={() => setShowForm((v) => !v)}
                        data-pan="production-add-entry"
                    >
                        <Plus className="mr-2 size-4" />
                        Add entry
                    </Button>
                </div>

                {showForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle>New entry</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleCreate} className="max-w-md space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="date">Date *</Label>
                                    <Input
                                        id="date"
                                        name="date"
                                        type="date"
                                        value={date}
                                        onChange={(e) => setDate(e.target.value)}
                                        required
                                    />
                                    <InputError message={errors?.date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="trip">Trip *</Label>
                                    <Input
                                        id="trip"
                                        name="trip"
                                        value={trip}
                                        onChange={(e) => setTrip(e.target.value)}
                                        required
                                    />
                                    <InputError message={errors?.trip} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="qty">Qty *</Label>
                                    <Input
                                        id="qty"
                                        name="qty"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={qty}
                                        onChange={(e) => setQty(e.target.value)}
                                        required
                                    />
                                    <InputError message={errors?.qty} />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={isSubmitting}>
                                        Save
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => {
                                            setShowForm(false);
                                            setDate('');
                                            setTrip('');
                                            setQty('');
                                        }}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Entries</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {entries.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No entries yet. Click &quot;Add entry&quot; to create one.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="px-4 py-2 text-left font-medium">Date</th>
                                            <th className="px-4 py-2 text-left font-medium">Trip</th>
                                            <th className="px-4 py-2 text-left font-medium">Qty</th>
                                            <th className="px-4 py-2 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {entries.map((entry) => (
                                            <tr key={entry.id} className="border-b last:border-0">
                                                <td className="px-4 py-2">{formatDateOnly(entry.date)}</td>
                                                <td className="px-4 py-2">{entry.trip}</td>
                                                <td className="px-4 py-2">{entry.qty}</td>
                                                <td className="px-4 py-2 text-right">
                                                    <Link
                                                        href={`${basePath}/${entry.id}/edit`}
                                                        className="text-primary hover:underline inline-flex items-center gap-1 mr-2"
                                                        data-pan="production-edit-entry"
                                                    >
                                                        <Pencil className="size-4" />
                                                        Edit
                                                    </Link>
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDelete(entry.id)}
                                                        className="text-destructive hover:underline inline-flex items-center gap-1"
                                                        data-pan="production-delete-entry"
                                                    >
                                                        <Trash2 className="size-4" />
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
