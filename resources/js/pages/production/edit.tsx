import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import InputError from '@/components/input-error';
import type { ProductionType } from './index';

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
    entry: ProductionEntry;
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

export default function ProductionEdit({ entry, type }: Props) {
    const { errors } = usePage<{ errors?: Record<string, string> }>().props;
    const basePath = productionBasePath(type);
    const title = productionTitle(type);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title, href: basePath },
        { title: 'Edit', href: `${basePath}/${entry.id}/edit` },
    ];

    const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const formData = {
            date: (form.querySelector('[name="date"]') as HTMLInputElement).value,
            trip: (form.querySelector('[name="trip"]') as HTMLInputElement).value,
            qty: (form.querySelector('[name="qty"]') as HTMLInputElement).value,
        };
        router.patch(`${basePath}/${entry.id}`, formData, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit – ${title}`} />
            <div className="space-y-6">
                <h1 className="text-2xl font-semibold">Edit entry</h1>
                <form onSubmit={handleSubmit} className="max-w-md space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="date">Date *</Label>
                        <Input
                            id="date"
                            name="date"
                            type="date"
                            defaultValue={formatDateOnly(entry.date)}
                            required
                        />
                        <InputError message={errors?.date} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="trip">Trip *</Label>
                        <Input
                            id="trip"
                            name="trip"
                            defaultValue={entry.trip}
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
                            defaultValue={entry.qty}
                            required
                        />
                        <InputError message={errors?.qty} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit">Update</Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href={basePath}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
