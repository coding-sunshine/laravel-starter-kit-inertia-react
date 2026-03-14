import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Loader2, Sparkles, Star } from 'lucide-react';
import { useState } from 'react';

interface ColdOutreachTemplate {
    id: number;
    name: string;
    channel: string;
    subject: string | null;
    body: string;
    tone: string | null;
    ai_generated: boolean;
    created_at: string;
}

interface Props {
    templates: {
        data: ColdOutreachTemplate[];
        total: number;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'CRM', href: '/contacts' },
    { title: 'Lead Generation', href: '/lead-generation' },
    { title: 'Cold Outreach Builder', href: '/cold-outreach' },
];

interface GeneratedTemplate {
    id: number;
    subject: string | null;
    body: string;
    ctas: string[];
}

export default function ColdOutreachIndexPage({ templates }: Props) {
    const [channel, setChannel] = useState<'email' | 'sms'>('email');
    const [tone, setTone] = useState<string>('professional');
    const [firstName, setFirstName] = useState('');
    const [suburb, setSuburb] = useState('');
    const [propertyType, setPropertyType] = useState('');
    const [isGenerating, setIsGenerating] = useState(false);
    const [generated, setGenerated] = useState<GeneratedTemplate | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleGenerate = async () => {
        setIsGenerating(true);
        setError(null);

        try {
            const res = await axios.post('/cold-outreach/generate', {
                channel,
                tone,
                context: {
                    first_name: firstName || 'there',
                    suburb,
                    property_type: propertyType,
                    name: `AI ${channel} — ${tone}`,
                },
            });
            setGenerated(res.data.template);
        } catch {
            setError('Failed to generate copy. Please try again.');
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Cold Outreach Builder" />

            <div className="space-y-6 p-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Cold Outreach Builder</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        AI-powered email and SMS templates with A/B variants and CTA suggestions
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {/* Generator */}
                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div className="mb-5 flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-primary" />
                            <h2 className="font-semibold text-gray-900">AI Copy Generator</h2>
                        </div>

                        <div className="space-y-4">
                            {/* Channel */}
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Channel</label>
                                <div className="flex gap-2">
                                    {(['email', 'sms'] as const).map((c) => (
                                        <button
                                            key={c}
                                            onClick={() => setChannel(c)}
                                            className={`flex-1 rounded-lg border py-2 text-sm font-medium capitalize transition-colors ${
                                                channel === c
                                                    ? 'border-primary bg-primary/10 text-primary'
                                                    : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                            }`}
                                        >
                                            {c === 'email' ? '📧 Email' : '📱 SMS'}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Tone */}
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">Tone</label>
                                <select
                                    value={tone}
                                    onChange={(e) => setTone(e.target.value)}
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                >
                                    <option value="professional">Professional</option>
                                    <option value="friendly">Friendly</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="casual">Casual</option>
                                </select>
                            </div>

                            {/* Context */}
                            <div>
                                <label className="mb-1 block text-sm font-medium text-gray-700">
                                    Lead First Name (optional)
                                </label>
                                <input
                                    type="text"
                                    value={firstName}
                                    onChange={(e) => setFirstName(e.target.value)}
                                    placeholder="e.g. Sarah"
                                    className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Suburb</label>
                                    <input
                                        type="text"
                                        value={suburb}
                                        onChange={(e) => setSuburb(e.target.value)}
                                        placeholder="e.g. Surry Hills"
                                        className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-medium text-gray-700">Property Type</label>
                                    <input
                                        type="text"
                                        value={propertyType}
                                        onChange={(e) => setPropertyType(e.target.value)}
                                        placeholder="e.g. apartment"
                                        className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                    />
                                </div>
                            </div>

                            <button
                                onClick={handleGenerate}
                                disabled={isGenerating}
                                className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-white hover:bg-primary/90 disabled:opacity-60"
                            >
                                {isGenerating ? (
                                    <>
                                        <Loader2 className="h-4 w-4 animate-spin" />
                                        Generating...
                                    </>
                                ) : (
                                    <>
                                        <Sparkles className="h-4 w-4" />
                                        Generate AI Copy
                                    </>
                                )}
                            </button>

                            {error && <p className="text-sm text-red-600">{error}</p>}
                        </div>
                    </div>

                    {/* Generated Result */}
                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h2 className="mb-4 font-semibold text-gray-900">Generated Copy</h2>

                        {!generated ? (
                            <div className="flex h-48 items-center justify-center text-center text-gray-400">
                                <div>
                                    <Sparkles className="mx-auto mb-2 h-8 w-8 text-gray-200" />
                                    <p className="text-sm">Your generated copy will appear here</p>
                                </div>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {generated.subject && (
                                    <div>
                                        <p className="mb-1 text-xs font-medium text-gray-500 uppercase tracking-wide">
                                            Subject
                                        </p>
                                        <p className="rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-900">
                                            {generated.subject}
                                        </p>
                                    </div>
                                )}
                                <div>
                                    <p className="mb-1 text-xs font-medium text-gray-500 uppercase tracking-wide">
                                        Body
                                    </p>
                                    <div className="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 whitespace-pre-wrap">
                                        {generated.body}
                                    </div>
                                </div>
                                {generated.ctas && generated.ctas.length > 0 && (
                                    <div>
                                        <p className="mb-1 text-xs font-medium text-gray-500 uppercase tracking-wide">
                                            CTA
                                        </p>
                                        {generated.ctas.map((cta, i) => (
                                            <span
                                                key={i}
                                                className="inline-block rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                                            >
                                                {cta}
                                            </span>
                                        ))}
                                    </div>
                                )}
                                <p className="text-xs text-gray-400">
                                    ✨ AI-generated — review before sending
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Saved Templates */}
                <div className="rounded-xl border border-gray-100 bg-white shadow-sm">
                    <div className="border-b border-gray-100 px-5 py-4">
                        <h2 className="font-semibold text-gray-900">Saved Templates ({templates.total})</h2>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {templates.data.length === 0 && (
                            <p className="px-5 py-8 text-center text-sm text-gray-400">
                                No templates yet — generate one above
                            </p>
                        )}
                        {templates.data.map((tpl) => (
                            <div key={tpl.id} className="flex items-start gap-3 px-5 py-4">
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2">
                                        <p className="truncate text-sm font-medium text-gray-900">{tpl.name}</p>
                                        {tpl.ai_generated && (
                                            <span className="flex-shrink-0 rounded-full bg-purple-100 px-1.5 py-0.5 text-xs text-purple-700">
                                                AI
                                            </span>
                                        )}
                                        <span className="flex-shrink-0 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 capitalize">
                                            {tpl.channel}
                                        </span>
                                    </div>
                                    {tpl.subject && (
                                        <p className="mt-0.5 truncate text-xs text-gray-500">Subj: {tpl.subject}</p>
                                    )}
                                </div>
                                {tpl.tone && (
                                    <span className="flex-shrink-0 text-xs text-gray-400 capitalize">{tpl.tone}</span>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
