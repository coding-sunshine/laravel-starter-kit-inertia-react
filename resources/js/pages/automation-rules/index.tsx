import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface AutomationRule {
    id: number;
    name: string;
    description: string | null;
    event: string;
    is_active: boolean;
    run_count: number;
    last_run_at: string | null;
}

interface Props {
    rules: AutomationRule[];
}

const EVENTS = [
    { label: 'Contact Stage Changed', value: 'contact.stage_changed' },
    { label: 'Sale Status Changed', value: 'sale.status_changed' },
    { label: 'Task Created', value: 'task.created' },
    { label: 'Task Completed', value: 'task.completed' },
    { label: 'Lead Captured', value: 'lead.captured' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Automation Rules', href: '/automation-rules' },
];

export default function AutomationRulesIndexPage({ rules }: Props) {
    const [showForm, setShowForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        description: '',
        event: 'contact.stage_changed',
        is_active: true,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/automation-rules', {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function handleToggle(rule: AutomationRule) {
        router.patch(`/automation-rules/${rule.id}`, { is_active: !rule.is_active });
    }

    function handleDelete(id: number) {
        if (!confirm('Delete this automation rule?')) return;
        router.delete(`/automation-rules/${id}`);
    }

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Automation Rules" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Automation Rules</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Automate actions when CRM events occur.</p>
                    </div>
                    <button
                        data-pan="automation-rules-tab"
                        onClick={() => setShowForm(true)}
                        className="inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700"
                    >
                        <Plus className="h-4 w-4" />
                        Add rule
                    </button>
                </div>

                {showForm && (
                    <form onSubmit={handleSubmit} className="rounded-lg border p-4 shadow-sm">
                        <h2 className="mb-4 font-semibold">New Automation Rule</h2>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="col-span-2">
                                <label className="mb-1 block text-sm font-medium">Name</label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="w-full rounded border px-3 py-1.5 text-sm"
                                    placeholder="e.g. Notify agent on hot lead"
                                    required
                                />
                                {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="mb-1 block text-sm font-medium">Trigger Event</label>
                                <select
                                    value={data.event}
                                    onChange={(e) => setData('event', e.target.value)}
                                    className="w-full rounded border px-3 py-1.5 text-sm"
                                >
                                    {EVENTS.map((ev) => (
                                        <option key={ev.value} value={ev.value}>{ev.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex items-center gap-2 pt-5">
                                <input
                                    type="checkbox"
                                    id="is_active"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                <label htmlFor="is_active" className="text-sm">Active</label>
                            </div>
                            <div className="col-span-2">
                                <label className="mb-1 block text-sm font-medium">Description (optional)</label>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="w-full rounded border px-3 py-1.5 text-sm"
                                    rows={2}
                                    placeholder="What does this rule do?"
                                />
                            </div>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-md bg-blue-600 px-4 py-1.5 text-sm text-white hover:bg-blue-700 disabled:opacity-60"
                            >
                                Save Rule
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowForm(false)}
                                className="rounded-md border px-4 py-1.5 text-sm hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                )}

                {rules.length === 0 ? (
                    <p className="text-muted-foreground py-8 text-center text-sm">No automation rules yet. Add one to get started.</p>
                ) : (
                    <div className="space-y-3">
                        {rules.map((rule) => (
                            <div key={rule.id} className="flex items-start justify-between rounded-lg border p-4">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <h3 className="font-medium">{rule.name}</h3>
                                        <span
                                            className={`rounded-full px-2 py-0.5 text-xs ${rule.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'}`}
                                        >
                                            {rule.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                    {rule.description && (
                                        <p className="text-muted-foreground mt-0.5 text-sm">{rule.description}</p>
                                    )}
                                    <div className="text-muted-foreground mt-1 flex gap-4 text-xs">
                                        <span>Event: <code className="bg-gray-100 px-1 rounded">{rule.event}</code></span>
                                        <span>Runs: {rule.run_count}</span>
                                        {rule.last_run_at && <span>Last run: {new Date(rule.last_run_at).toLocaleDateString()}</span>}
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        onClick={() => handleToggle(rule)}
                                        className={`rounded px-2 py-1 text-xs ${rule.is_active ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200'}`}
                                    >
                                        {rule.is_active ? 'Disable' : 'Enable'}
                                    </button>
                                    <button
                                        onClick={() => handleDelete(rule.id)}
                                        className="text-red-500 hover:text-red-700"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
