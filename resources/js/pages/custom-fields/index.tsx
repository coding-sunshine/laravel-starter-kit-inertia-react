import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface CustomField {
    id: number;
    name: string;
    key: string;
    type: string;
    entity_type: string;
    is_required: boolean;
    sort_order: number;
    options: string[] | null;
}

interface Props {
    customFields: Record<string, CustomField[]>;
}

const ENTITY_TYPES = [
    { label: 'Contact', value: 'contact' },
    { label: 'Sale', value: 'sale' },
    { label: 'Lot', value: 'lot' },
    { label: 'Project', value: 'project' },
];

const FIELD_TYPES = [
    { label: 'Text', value: 'text' },
    { label: 'Number', value: 'number' },
    { label: 'Date', value: 'date' },
    { label: 'Select', value: 'select' },
    { label: 'Multi Select', value: 'multi_select' },
    { label: 'Checkbox', value: 'checkbox' },
    { label: 'URL', value: 'url' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Custom Fields', href: '/custom-fields' },
];

function slugify(str: string): string {
    return str
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_|_$/g, '');
}

export default function CustomFieldsIndexPage({ customFields }: Props) {
    const [addingFor, setAddingFor] = useState<string | null>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        entity_type: '',
        name: '',
        key: '',
        type: 'text',
        is_required: false,
        sort_order: 0,
        options: [] as string[],
    });

    function handleAdd(entityType: string) {
        setAddingFor(entityType);
        setData({ ...data, entity_type: entityType, name: '', key: '', type: 'text', is_required: false });
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/custom-fields', {
            onSuccess: () => {
                reset();
                setAddingFor(null);
            },
        });
    }

    function handleDelete(id: number) {
        if (!confirm('Delete this custom field? All associated values will be removed.')) return;
        router.delete(`/custom-fields/${id}`);
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Custom Fields" />

            <div className="space-y-8 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Custom Fields</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Add custom fields to contacts, sales, lots, and projects.</p>
                </div>

                {ENTITY_TYPES.map(({ label, value }) => (
                    <div key={value} className="rounded-lg border p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="font-semibold capitalize">{label} Fields</h2>
                            <button
                                data-pan="custom-fields-tab"
                                onClick={() => handleAdd(value)}
                                className="inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700"
                            >
                                <Plus className="h-4 w-4" />
                                Add field
                            </button>
                        </div>

                        {(customFields[value] ?? []).length === 0 && addingFor !== value && (
                            <p className="text-muted-foreground text-sm">No custom fields yet.</p>
                        )}

                        <div className="space-y-2">
                            {(customFields[value] ?? []).map((field) => (
                                <div key={field.id} className="flex items-center justify-between rounded border bg-gray-50 px-3 py-2 dark:bg-gray-800">
                                    <div>
                                        <span className="font-medium">{field.name}</span>
                                        <span className="text-muted-foreground ml-2 text-xs">({field.type})</span>
                                        {field.is_required && <span className="ml-2 text-xs text-red-600">required</span>}
                                        <span className="text-muted-foreground ml-2 text-xs">key: {field.key}</span>
                                    </div>
                                    <button
                                        onClick={() => handleDelete(field.id)}
                                        className="text-red-500 hover:text-red-700"
                                        title="Delete field"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            ))}
                        </div>

                        {addingFor === value && (
                            <form onSubmit={handleSubmit} className="mt-4 space-y-3 rounded-md border bg-white p-4 dark:bg-gray-900">
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Field Name</label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => {
                                                setData('name', e.target.value);
                                                setData('key', slugify(e.target.value));
                                            }}
                                            className="w-full rounded border px-3 py-1.5 text-sm"
                                            placeholder="e.g. Budget Range"
                                            required
                                        />
                                        {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Key (slug)</label>
                                        <input
                                            type="text"
                                            value={data.key}
                                            onChange={(e) => setData('key', e.target.value)}
                                            className="w-full rounded border px-3 py-1.5 text-sm font-mono"
                                            placeholder="e.g. budget_range"
                                            required
                                        />
                                        {errors.key && <p className="mt-1 text-xs text-red-500">{errors.key}</p>}
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-medium">Type</label>
                                        <select
                                            value={data.type}
                                            onChange={(e) => setData('type', e.target.value)}
                                            className="w-full rounded border px-3 py-1.5 text-sm"
                                        >
                                            {FIELD_TYPES.map((t) => (
                                                <option key={t.value} value={t.value}>{t.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="flex items-center gap-2 pt-5">
                                        <input
                                            type="checkbox"
                                            id="is_required"
                                            checked={data.is_required}
                                            onChange={(e) => setData('is_required', e.target.checked)}
                                        />
                                        <label htmlFor="is_required" className="text-sm">Required</label>
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="rounded-md bg-blue-600 px-4 py-1.5 text-sm text-white hover:bg-blue-700 disabled:opacity-60"
                                    >
                                        Save Field
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setAddingFor(null)}
                                        className="rounded-md border px-4 py-1.5 text-sm hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                ))}
            </div>
        </AppSidebarLayout>
    );
}
