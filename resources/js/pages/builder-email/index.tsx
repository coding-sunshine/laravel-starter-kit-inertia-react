import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { CheckCircle, Loader2, Mail, Send } from 'lucide-react';
import { useState } from 'react';

interface Project {
    id: number;
    title: string;
    stage: string;
}

interface TemplateType {
    value: string;
    label: string;
}

interface EmailLog {
    id: number;
    template_type: string;
    recipient_email: string;
    recipient_name: string | null;
    subject: string;
    status: string;
    created_at: string;
    project?: { id: number; title: string };
    contact?: { id: number; first_name: string; last_name: string };
    sent_by_user?: { id: number; name: string };
}

interface Props {
    projects: Project[];
    recent_logs: EmailLog[];
    template_types: TemplateType[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Email to Builder', href: '/builder-email' },
];

const templateDescriptions: Record<string, string> = {
    price_list: 'Request current price list and availability from the builder.',
    more_info: 'Request more detailed project information and specifications.',
    hold_request: 'Request to hold a specific lot or property for a client.',
    property_request: 'Request properties matching specific client requirements.',
};

export default function BuilderEmailPage({ projects, recent_logs, template_types }: Props) {
    const [templateType, setTemplateType] = useState<string>('price_list');
    const [recipientEmail, setRecipientEmail] = useState('');
    const [recipientName, setRecipientName] = useState('');
    const [projectId, setProjectId] = useState<string>('');
    const [message, setMessage] = useState('');
    const [payload, setPayload] = useState<Record<string, string>>({});
    const [isSending, setIsSending] = useState(false);
    const [sent, setSent] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSend = async () => {
        if (!recipientEmail) return;

        setIsSending(true);
        setError(null);
        setSent(false);

        try {
            await axios.post('/builder-email/send', {
                template_type: templateType,
                recipient_email: recipientEmail,
                recipient_name: recipientName,
                project_id: projectId || undefined,
                message,
                payload,
            });
            setSent(true);
            setRecipientEmail('');
            setRecipientName('');
            setMessage('');
            setPayload({});
        } catch (err: unknown) {
            const msg = (err as { response?: { data?: { message?: string } } })?.response?.data?.message;
            setError(msg || 'Failed to send email. Please try again.');
        } finally {
            setIsSending(false);
        }
    };

    const updatePayload = (key: string, value: string) => {
        setPayload((prev) => ({ ...prev, [key]: value }));
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Email to Builder" />

            <div className="space-y-6 p-6">
                <div className="flex items-center gap-3">
                    <Mail className="text-primary h-6 w-6" />
                    <div>
                        <h1 className="text-2xl font-bold">Email to Builder</h1>
                        <p className="text-muted-foreground text-sm">
                            Send templated emails to builders directly from the CRM.
                        </p>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-5">
                    {/* Compose Panel */}
                    <div className="lg:col-span-3 space-y-5 rounded-lg border bg-card p-5">
                        <h2 className="font-semibold">Compose Email</h2>

                        {/* Template Type */}
                        <div>
                            <label className="text-muted-foreground mb-2 block text-xs font-medium">Template Type</label>
                            <div className="grid grid-cols-2 gap-2">
                                {template_types.map((t) => (
                                    <button
                                        key={t.value}
                                        onClick={() => setTemplateType(t.value)}
                                        className={`rounded-md border p-3 text-left text-sm transition-colors ${
                                            templateType === t.value
                                                ? 'border-primary bg-primary/5 font-medium text-primary'
                                                : 'border-border hover:border-primary/40 hover:bg-accent/30'
                                        }`}
                                    >
                                        {t.label}
                                    </button>
                                ))}
                            </div>
                            {templateDescriptions[templateType] && (
                                <p className="mt-2 text-xs text-muted-foreground">{templateDescriptions[templateType]}</p>
                            )}
                        </div>

                        {/* Recipient */}
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs font-medium">Builder Name *</label>
                                <input
                                    type="text"
                                    value={recipientName}
                                    onChange={(e) => setRecipientName(e.target.value)}
                                    placeholder="Builder / Company name"
                                    className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label className="text-muted-foreground mb-1 block text-xs font-medium">Builder Email *</label>
                                <input
                                    type="email"
                                    value={recipientEmail}
                                    onChange={(e) => setRecipientEmail(e.target.value)}
                                    placeholder="builder@example.com"
                                    className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                        </div>

                        {/* Project */}
                        <div>
                            <label className="text-muted-foreground mb-1 block text-xs font-medium">Project (optional)</label>
                            <select
                                value={projectId}
                                onChange={(e) => setProjectId(e.target.value)}
                                className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                                <option value="">— Select project —</option>
                                {projects.map((p) => (
                                    <option key={p.id} value={p.id}>{p.title}</option>
                                ))}
                            </select>
                        </div>

                        {/* Template-specific fields */}
                        {templateType === 'hold_request' && (
                            <div className="grid gap-3 sm:grid-cols-2 rounded-md bg-muted/30 p-3">
                                <div>
                                    <label className="text-muted-foreground mb-1 block text-xs font-medium">Lot / Property</label>
                                    <input
                                        type="text"
                                        value={payload['lot_number'] ?? ''}
                                        onChange={(e) => updatePayload('lot_number', e.target.value)}
                                        placeholder="e.g. Lot 42"
                                        className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                </div>
                                <div>
                                    <label className="text-muted-foreground mb-1 block text-xs font-medium">Client Name</label>
                                    <input
                                        type="text"
                                        value={payload['client_name'] ?? ''}
                                        onChange={(e) => updatePayload('client_name', e.target.value)}
                                        placeholder="Client full name"
                                        className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                </div>
                                <div>
                                    <label className="text-muted-foreground mb-1 block text-xs font-medium">Hold Duration</label>
                                    <input
                                        type="text"
                                        value={payload['hold_duration'] ?? ''}
                                        onChange={(e) => updatePayload('hold_duration', e.target.value)}
                                        placeholder="e.g. 7 days"
                                        className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                </div>
                            </div>
                        )}

                        {templateType === 'property_request' && (
                            <div className="grid gap-3 sm:grid-cols-2 rounded-md bg-muted/30 p-3">
                                {[
                                    { key: 'bedrooms', label: 'Bedrooms', placeholder: 'e.g. 4' },
                                    { key: 'bathrooms', label: 'Bathrooms', placeholder: 'e.g. 2' },
                                    { key: 'budget', label: 'Budget ($)', placeholder: '650000' },
                                    { key: 'land_size', label: 'Land Size', placeholder: 'e.g. 450m²' },
                                    { key: 'preferred_stage', label: 'Preferred Stage', placeholder: 'Stage 2' },
                                ].map(({ key, label, placeholder }) => (
                                    <div key={key}>
                                        <label className="text-muted-foreground mb-1 block text-xs font-medium">{label}</label>
                                        <input
                                            type="text"
                                            value={payload[key] ?? ''}
                                            onChange={(e) => updatePayload(key, e.target.value)}
                                            placeholder={placeholder}
                                            className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                        />
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Additional message */}
                        <div>
                            <label className="text-muted-foreground mb-1 block text-xs font-medium">Additional Message</label>
                            <textarea
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                rows={3}
                                placeholder="Any additional notes or requirements…"
                                className="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        {error && (
                            <p className="text-sm text-destructive">{error}</p>
                        )}

                        {sent && (
                            <div className="flex items-center gap-2 rounded-md bg-green-50 p-3 text-sm text-green-700">
                                <CheckCircle className="h-4 w-4" />
                                Email sent successfully!
                            </div>
                        )}

                        <button
                            onClick={handleSend}
                            disabled={isSending || !recipientEmail || !recipientName}
                            className="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                        >
                            {isSending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}
                            {isSending ? 'Sending…' : 'Send Email'}
                        </button>
                    </div>

                    {/* Log Panel */}
                    <div className="lg:col-span-2 rounded-lg border bg-card">
                        <div className="border-b px-4 py-3">
                            <h2 className="font-semibold text-sm">Recent Emails Sent</h2>
                        </div>
                        {recent_logs.length === 0 ? (
                            <div className="flex flex-col items-center justify-center p-8 text-center">
                                <Mail className="text-muted-foreground/30 h-10 w-10 mb-2" />
                                <p className="text-muted-foreground text-sm">No emails sent yet</p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {recent_logs.map((log) => (
                                    <div key={log.id} className="px-4 py-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium">{log.recipient_email}</p>
                                                <p className="text-muted-foreground text-xs capitalize">{log.template_type.replace('_', ' ')}</p>
                                                {log.project && (
                                                    <p className="text-muted-foreground text-xs truncate">{log.project.title}</p>
                                                )}
                                            </div>
                                            <div className="shrink-0 text-right">
                                                <span className="inline-block rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                                    {log.status}
                                                </span>
                                                <p className="text-muted-foreground mt-1 text-xs">
                                                    {new Date(log.created_at).toLocaleDateString()}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
