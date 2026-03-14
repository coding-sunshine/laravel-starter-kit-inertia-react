import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { Building2, Search, Sparkles } from 'lucide-react';
import { useState } from 'react';

interface MatchResult {
    reply: string;
    conversation_id: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'AI', href: '/ai/concierge' },
    { title: 'Property Concierge', href: '/ai/concierge' },
];

export default function ConciergeIndexPage() {
    const [contactId, setContactId] = useState('');
    const [message, setMessage] = useState('');
    const [result, setResult] = useState<MatchResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [conversationId, setConversationId] = useState<string | null>(null);
    const [history, setHistory] = useState<Array<{ query: string; reply: string }>>([]);

    const findMatches = () => {
        const query = message.trim();
        if (!query || loading) return;

        setLoading(true);

        router.post(
            '/ai/concierge/match',
            {
                contact_id: contactId ? parseInt(contactId) : undefined,
                message: query,
                conversation_id: conversationId,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    const data = page.props as Record<string, unknown>;
                    const flash = (data.flash ?? {}) as Record<string, unknown>;
                    const reply = (flash.reply as string) ?? '';
                    const newConvId = (flash.conversation_id as string) ?? conversationId;
                    setConversationId(newConvId);
                    setResult({ reply, conversation_id: newConvId ?? '' });
                    setHistory((prev) => [...prev, { query, reply }]);
                    setMessage('');
                    setLoading(false);
                },
                onError: () => {
                    setLoading(false);
                },
            },
        );
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Property Concierge" />
            <div
                className="flex h-full flex-1 flex-col gap-6 p-6"
                data-pan="ai-concierge-tab"
            >
                {/* Header */}
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100">
                        <Building2 className="size-5 text-purple-600" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">GPT Property Concierge</h1>
                        <p className="text-sm text-gray-500">
                            Match buyers to suitable properties based on their preferences and budget
                        </p>
                    </div>
                </div>

                {/* Search Form */}
                <div className="rounded-xl border bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-sm font-semibold text-gray-700">Find Property Matches</h2>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Contact ID (optional)
                            </label>
                            <input
                                type="number"
                                value={contactId}
                                onChange={(e) => setContactId(e.target.value)}
                                placeholder="e.g. 42"
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Buyer Requirements
                            </label>
                            <textarea
                                value={message}
                                onChange={(e) => setMessage(e.target.value)}
                                placeholder="e.g. 3 bedroom house, budget $600k, prefer Northside suburbs, investment property"
                                rows={3}
                                className="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                            />
                        </div>
                        <button
                            onClick={findMatches}
                            disabled={!message.trim() || loading}
                            className="flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {loading ? (
                                <>
                                    <Search className="size-4 animate-spin" />
                                    Searching...
                                </>
                            ) : (
                                <>
                                    <Sparkles className="size-4" />
                                    Find Matches
                                </>
                            )}
                        </button>
                    </div>
                </div>

                {/* Results History */}
                {history.length > 0 && (
                    <div className="space-y-4">
                        {history.map((item, index) => (
                            <div key={index} className="rounded-xl border bg-white p-6 shadow-sm">
                                <p className="text-xs font-medium text-purple-600 mb-2">Query</p>
                                <p className="text-sm text-gray-700 mb-4 font-medium">{item.query}</p>
                                <p className="text-xs font-medium text-gray-500 mb-2">Concierge Response</p>
                                <p className="text-sm text-gray-900 whitespace-pre-wrap">{item.reply}</p>
                            </div>
                        ))}
                    </div>
                )}

                {history.length === 0 && (
                    <div className="flex flex-col items-center justify-center gap-4 py-12 text-center">
                        <div className="rounded-full bg-purple-50 p-4">
                            <Building2 className="size-8 text-purple-300" />
                        </div>
                        <div>
                            <p className="font-medium text-gray-700">No searches yet</p>
                            <p className="mt-1 text-sm text-gray-400">
                                Enter buyer requirements above to find matching properties.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppSidebarLayout>
    );
}
