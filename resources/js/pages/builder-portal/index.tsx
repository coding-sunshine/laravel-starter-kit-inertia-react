import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Building2, CheckCircle, Plus, XCircle } from 'lucide-react';
import { useState } from 'react';

interface Portal {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    contact_email: string;
}

interface Project {
    id: number;
    name: string;
}

interface Props {
    portals: Portal[];
    projects: Project[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Builder Portals', href: '/builder-portal' },
];

export default function BuilderPortalIndexPage({ portals }: Props) {
    const [showForm, setShowForm] = useState(false);

    const { data, setData, processing, errors, reset } = useForm({
        name: '',
        slug: '',
        contact_email: '',
        contact_phone: '',
        primary_color: '#3b82f6',
        show_prices: false as boolean,
        show_agent_details: false as boolean,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        router.post('/builder-portal', data, {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Builder Portals" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4" data-pan="builder-portal-index">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Builder Portals</h1>
                        <p className="text-muted-foreground">{portals.length} portals configured</p>
                    </div>
                    <button
                        onClick={() => setShowForm((v) => !v)}
                        className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                        data-pan="builder-portal-create"
                    >
                        <Plus className="h-4 w-4" />
                        Create Portal
                    </button>
                </div>

                {/* Create Portal Form */}
                {showForm && (
                    <div className="rounded-lg border bg-card p-5">
                        <h2 className="mb-4 text-lg font-semibold">New Builder Portal</h2>
                        <form onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium">Name</label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Acme Builders"
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium">Slug</label>
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    placeholder="acme-builders"
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                />
                                {errors.slug && <p className="text-xs text-destructive">{errors.slug}</p>}
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium">Contact Email</label>
                                <input
                                    type="email"
                                    value={data.contact_email}
                                    onChange={(e) => setData('contact_email', e.target.value)}
                                    placeholder="contact@acme.com"
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                />
                                {errors.contact_email && (
                                    <p className="text-xs text-destructive">{errors.contact_email}</p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium">Contact Phone</label>
                                <input
                                    type="tel"
                                    value={data.contact_phone}
                                    onChange={(e) => setData('contact_phone', e.target.value)}
                                    placeholder="+61 400 000 000"
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                />
                                {errors.contact_phone && (
                                    <p className="text-xs text-destructive">{errors.contact_phone}</p>
                                )}
                            </div>

                            <div className="flex flex-col gap-1">
                                <label className="text-sm font-medium">Primary Colour</label>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="color"
                                        value={data.primary_color}
                                        onChange={(e) => setData('primary_color', e.target.value)}
                                        className="h-9 w-16 cursor-pointer rounded border bg-background p-1"
                                    />
                                    <span className="text-sm text-muted-foreground">{data.primary_color}</span>
                                </div>
                                {errors.primary_color && (
                                    <p className="text-xs text-destructive">{errors.primary_color}</p>
                                )}
                            </div>

                            <div className="flex flex-col justify-center gap-3 sm:flex-row sm:items-center">
                                <label className="flex cursor-pointer items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={data.show_prices}
                                        onChange={(e) => setData('show_prices', e.target.checked)}
                                        className="rounded border"
                                    />
                                    Show Prices
                                </label>
                                <label className="flex cursor-pointer items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={data.show_agent_details}
                                        onChange={(e) => setData('show_agent_details', e.target.checked)}
                                        className="rounded border"
                                    />
                                    Show Agent Details
                                </label>
                            </div>

                            <div className="flex items-center gap-3 sm:col-span-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                                >
                                    Create Portal
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setShowForm(false)}
                                    className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                )}

                {/* Portal List */}
                {portals.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                        <Building2 className="mb-4 h-12 w-12 text-muted-foreground" />
                        <h2 className="text-lg font-semibold">No portals yet</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Create your first builder portal to get started.
                        </p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {portals.map((portal) => (
                            <div key={portal.id} className="rounded-lg border bg-card p-5 shadow-sm">
                                <div className="mb-3 flex items-start justify-between">
                                    <div>
                                        <h3 className="font-semibold">{portal.name}</h3>
                                        <p className="text-xs text-muted-foreground">/{portal.slug}</p>
                                    </div>
                                    {portal.is_active ? (
                                        <span className="flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                            <CheckCircle className="h-3 w-3" />
                                            Active
                                        </span>
                                    ) : (
                                        <span className="flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                            <XCircle className="h-3 w-3" />
                                            Inactive
                                        </span>
                                    )}
                                </div>
                                <p className="mb-4 text-sm text-muted-foreground">{portal.contact_email}</p>
                                <div className="flex gap-2">
                                    <a
                                        href={`/builder-portal/${portal.id}`}
                                        className="inline-flex items-center gap-1 rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                                        data-pan="builder-portal-view"
                                    >
                                        View Portal
                                    </a>
                                    <a
                                        href={`/builder-portal/${portal.id}/edit`}
                                        className="inline-flex items-center gap-1 rounded border px-3 py-1.5 text-xs font-medium hover:bg-accent"
                                    >
                                        Edit
                                    </a>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
