import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Globe, LayoutTemplate, Plus, X } from 'lucide-react';
import { useState } from 'react';

interface PuckTemplate {
    id: number;
    name: string;
    type: string;
    thumbnail_path: string | null;
    is_global: boolean;
}

interface Props {
    templates: PuckTemplate[];
    types: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Puck Templates', href: '/puck-templates' },
];

export default function PuckTemplatesIndexPage({ templates, types }: Props) {
    const [activeType, setActiveType] = useState<string>('all');
    const [showModal, setShowModal] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        type: types[0] ?? '',
        puck_content: '{}',
    });

    const filteredTemplates =
        activeType === 'all' ? templates : templates.filter((t) => t.type === activeType);

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/puck-templates', {
            onSuccess: () => {
                reset();
                setShowModal(false);
            },
        });
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Puck Templates" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4" data-pan="puck-templates-index">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Puck Templates</h1>
                        <p className="text-muted-foreground">{templates.length} templates available</p>
                    </div>
                    <button
                        onClick={() => setShowModal(true)}
                        className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        data-pan="puck-templates-create"
                    >
                        <Plus className="h-4 w-4" />
                        New Template
                    </button>
                </div>

                {/* Type filter tabs */}
                <div className="border-b">
                    <nav className="-mb-px flex gap-6 overflow-x-auto">
                        <button
                            onClick={() => setActiveType('all')}
                            className={`whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors ${
                                activeType === 'all'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            All
                            <span className="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                                {templates.length}
                            </span>
                        </button>
                        {types.map((type) => {
                            const count = templates.filter((t) => t.type === type).length;
                            return (
                                <button
                                    key={type}
                                    onClick={() => setActiveType(type)}
                                    className={`whitespace-nowrap border-b-2 pb-3 text-sm font-medium capitalize transition-colors ${
                                        activeType === type
                                            ? 'border-primary text-primary'
                                            : 'border-transparent text-muted-foreground hover:text-foreground'
                                    }`}
                                >
                                    {type}
                                    <span className="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                                        {count}
                                    </span>
                                </button>
                            );
                        })}
                    </nav>
                </div>

                {/* Template grid */}
                {filteredTemplates.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                        <LayoutTemplate className="mb-4 h-12 w-12 text-muted-foreground" />
                        <h2 className="text-lg font-semibold">No templates found</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {activeType === 'all'
                                ? 'Create your first template to get started.'
                                : `No templates of type "${activeType}" yet.`}
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {filteredTemplates.map((template) => (
                            <div
                                key={template.id}
                                className="group rounded-lg border bg-card shadow-sm transition-shadow hover:shadow-md"
                            >
                                {/* Thumbnail */}
                                <div className="relative aspect-video overflow-hidden rounded-t-lg bg-muted">
                                    {template.thumbnail_path ? (
                                        <img
                                            src={template.thumbnail_path}
                                            alt={template.name}
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-full items-center justify-center">
                                            <LayoutTemplate className="h-10 w-10 text-muted-foreground/50" />
                                        </div>
                                    )}
                                    {template.is_global && (
                                        <div className="absolute right-2 top-2">
                                            <span className="flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                                                <Globe className="h-3 w-3" />
                                                Global
                                            </span>
                                        </div>
                                    )}
                                </div>

                                <div className="p-4">
                                    <h3 className="font-medium">{template.name}</h3>
                                    <p className="mt-0.5 text-xs capitalize text-muted-foreground">{template.type}</p>
                                    <div className="mt-3 flex gap-2">
                                        <a
                                            href={`/puck-templates/${template.id}/edit`}
                                            className="inline-flex flex-1 items-center justify-center rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                                            data-pan="puck-templates-edit"
                                        >
                                            Edit
                                        </a>
                                        <button
                                            onClick={() =>
                                                router.delete(`/puck-templates/${template.id}`, {
                                                    onBefore: () =>
                                                        confirm(`Delete "${template.name}"?`),
                                                })
                                            }
                                            className="rounded border px-3 py-1.5 text-xs font-medium hover:bg-accent"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* New Template Modal */}
            {showModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-lg bg-background shadow-xl">
                        <div className="flex items-center justify-between border-b px-5 py-4">
                            <h2 className="text-lg font-semibold">New Template</h2>
                            <button
                                onClick={() => setShowModal(false)}
                                className="rounded-md p-1 hover:bg-accent"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <form onSubmit={handleSubmit} className="space-y-4 p-5">
                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium">Name</label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="My Template"
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                    autoFocus
                                />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium">Type</label>
                                <select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                >
                                    {types.map((type) => (
                                        <option key={type} value={type}>
                                            {type}
                                        </option>
                                    ))}
                                </select>
                                {errors.type && <p className="text-xs text-destructive">{errors.type}</p>}
                            </div>

                            <div className="flex gap-3 pt-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                >
                                    {processing ? 'Creating...' : 'Create Template'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppSidebarLayout>
    );
}
