import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Bot, MessageSquarePlus, Send, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';

/** Generate a unique id; works in environments where crypto.randomUUID is unavailable (e.g. non-HTTPS). */
function randomId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    const bytes = new Uint8Array(16);
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
        crypto.getRandomValues(bytes);
    } else {
        for (let i = 0; i < 16; i++) bytes[i] = Math.floor(Math.random() * 256);
    }
    bytes[6] = (bytes[6]! & 0x0f) | 0x40;
    bytes[8] = (bytes[8]! & 0x3f) | 0x80;
    const hex = [...bytes].map((b) => b!.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

interface Message {
    id: string;
    role: 'user' | 'assistant';
    content: string;
}

interface ConversationItem {
    id: string;
    title: string;
    updated_at: string;
}

interface Props {
    conversations: ConversationItem[];
    initial_messages: Message[];
    conversation_id: string | null;
}

export default function FleetAssistantIndex({
    conversations = [],
    initial_messages = [],
    conversation_id = null,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: dashboard().url },
        { title: 'Fleet', href: '/fleet' },
        { title: 'Assistant', href: '/fleet/assistant' },
    ];

    const [messages, setMessages] = useState<Message[]>(() =>
        initial_messages.map((m) => ({
            id: m.id,
            role: m.role as 'user' | 'assistant',
            content: m.content,
        })),
    );
    const [input, setInput] = useState('');
    const [conversationId, setConversationId] = useState<string | null>(conversation_id);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const abortRef = useRef<AbortController | null>(null);

    // Sync state when opening a different conversation (e.g. from sidebar or URL)
    useEffect(() => {
        setMessages(
            initial_messages.map((m) => ({
                id: m.id,
                role: m.role as 'user' | 'assistant',
                content: m.content,
            })),
        );
        setConversationId(conversation_id);
    }, [conversation_id]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const updateUrl = useCallback((id: string | null) => {
        const url = id ? `/fleet/assistant?conversation_id=${id}` : '/fleet/assistant';
        window.history.replaceState({}, '', url);
    }, []);

    const sendStreaming = useCallback(
        async (text: string) => {
            const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
            abortRef.current = new AbortController();
            const res = await fetch('/fleet/assistant/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/x-ndjson',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ message: text, conversation_id: conversationId }),
                signal: abortRef.current.signal,
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                setError(data?.message ?? `Error ${res.status}`);
                setLoading(false);
                return;
            }

            const reader = res.body?.getReader();
            if (!reader) {
                setError('No response body');
                setLoading(false);
                return;
            }

            const decoder = new TextDecoder();
            let buffer = '';
            let assistantMessageId: string | null = null;
            let streamHadContent = false;
            let currentConvId: string | null = conversationId;

            const processLine = (line: string) => {
                try {
                    const chunk = JSON.parse(line) as {
                        type?: string;
                        conversationId?: string;
                        delta?: string;
                        content?: string;
                        message?: string;
                    };
                    switch (chunk.type) {
                        case 'CONVERSATION_CREATED':
                            if (chunk.conversationId) {
                                currentConvId = chunk.conversationId;
                                setConversationId(chunk.conversationId);
                                updateUrl(chunk.conversationId);
                            }
                            break;
                        case 'TEXT_MESSAGE_START':
                            assistantMessageId = randomId();
                            setMessages((prev) => [
                                ...prev,
                                { id: assistantMessageId!, role: 'assistant', content: '' },
                            ]);
                            break;
                        case 'TEXT_MESSAGE_CONTENT':
                            if (chunk.content !== undefined && assistantMessageId) {
                                streamHadContent = true;
                                setMessages((prev) =>
                                    prev.map((m) =>
                                        m.id === assistantMessageId
                                            ? { ...m, content: chunk.content ?? m.content }
                                            : m,
                                    ),
                                );
                            }
                            break;
                        case 'RUN_FINISHED':
                            setLoading(false);
                            break;
                        case 'ERROR':
                            setError(chunk.message ?? 'AI request failed.');
                            setLoading(false);
                            break;
                        default:
                            break;
                    }
                } catch {
                    // ignore malformed lines
                }
            };

            try {
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() ?? '';
                    for (const line of lines) {
                        if (line.trim()) processLine(line);
                    }
                }
                if (buffer.trim()) processLine(buffer);

                // Stream returned no text (e.g. OpenRouter with tools only sent StreamEnd). Fallback to non-streaming prompt.
                if (!streamHadContent) {
                    const fallbackRes = await fetch('/fleet/assistant/prompt', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            message: text,
                            conversation_id: currentConvId,
                        }),
                    });
                    const fallbackData = await fallbackRes.json().catch(() => ({}));
                    if (fallbackRes.ok && fallbackData.reply) {
                        if (fallbackData.conversation_id) setConversationId(fallbackData.conversation_id);
                        if (assistantMessageId) {
                            setMessages((prev) =>
                                prev.map((m) =>
                                    m.id === assistantMessageId ? { ...m, content: fallbackData.reply } : m,
                                ),
                            );
                        } else {
                            setMessages((prev) => [
                                ...prev,
                                { id: randomId(), role: 'assistant', content: fallbackData.reply },
                            ]);
                        }
                    } else if (!fallbackRes.ok) {
                        setError(fallbackData?.message ?? 'Failed to get reply.');
                    }
                }
            } catch (err) {
                if ((err as Error).name !== 'AbortError') {
                    setError(err instanceof Error ? err.message : 'Stream failed');
                }
            } finally {
                setLoading(false);
                abortRef.current = null;
            }
        },
        [conversationId, updateUrl],
    );

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        const text = input.trim();
        if (!text || loading) return;

        setInput('');
        setError(null);
        const userMsg: Message = { id: randomId(), role: 'user', content: text };
        setMessages((prev) => [...prev, userMsg]);
        setLoading(true);

        try {
            await sendStreaming(text);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Request failed');
            setLoading(false);
        }
    };

    const openConversation = (id: string | null) => {
        if (id) {
            router.get('/fleet/assistant', { conversation_id: id });
        } else {
            router.get('/fleet/assistant');
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Fleet – Assistant" />
            <div className="flex h-full flex-1 gap-4 rounded-xl p-4">
                {/* Sidebar: conversation list */}
                <aside className="flex w-56 shrink-0 flex-col rounded-lg border bg-muted/20 p-2">
                    <Button
                        variant="outline"
                        size="sm"
                        className="mb-2 w-full justify-start gap-2"
                        onClick={() => openConversation(null)}
                        asChild
                    >
                        <Link href="/fleet/assistant" className="flex items-center gap-2">
                            <MessageSquarePlus className="size-4" />
                            New chat
                        </Link>
                    </Button>
                    <div className="flex-1 overflow-y-auto">
                        {conversations.length === 0 && (
                            <p className="px-2 py-4 text-center text-xs text-muted-foreground">No past chats</p>
                        )}
                        {conversations.map((c) => (
                            <button
                                key={c.id}
                                type="button"
                                onClick={() => openConversation(c.id)}
                                className={`w-full rounded-md px-2 py-2 text-left text-sm hover:bg-muted ${
                                    conversation_id === c.id ? 'bg-muted font-medium' : ''
                                }`}
                                title={c.title}
                            >
                                <span className="block truncate">{c.title}</span>
                                <span className="text-xs text-muted-foreground">
                                    {new Date(c.updated_at).toLocaleDateString()}
                                </span>
                            </button>
                        ))}
                    </div>
                </aside>

                {/* Main chat */}
                <div className="flex min-w-0 flex-1 flex-col gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Fleet Assistant</h1>
                        <p className="text-muted-foreground text-sm">
                            Ask about vehicles, drivers, trips, alerts, or search fleet documents (MOT, V5C,
                            insurance). Answers are scoped to your organization.
                        </p>
                    </div>

                    <div className="flex min-h-[400px] flex-1 flex-col rounded-lg border bg-muted/20">
                        <div className="flex-1 space-y-4 overflow-y-auto p-4">
                            {messages.length === 0 && !loading && (
                                <div className="flex flex-col items-center justify-center gap-2 py-12 text-center text-muted-foreground">
                                    <Bot className="size-12" />
                                    <p>Send a message to get started.</p>
                                    <p className="text-sm">
                                        e.g. &quot;List my vehicles&quot; or &quot;Any active alerts?&quot;
                                    </p>
                                </div>
                            )}
                            {messages.map((m) => (
                                <div
                                    key={m.id}
                                    className={`flex gap-3 ${m.role === 'user' ? 'justify-end' : ''}`}
                                >
                                    {m.role === 'assistant' && (
                                        <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                            <Bot className="size-4 text-primary" />
                                        </div>
                                    )}
                                    <div
                                        className={`max-w-[85%] rounded-lg px-4 py-2 text-sm ${
                                            m.role === 'user'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'border bg-background'
                                        }`}
                                    >
                                        <div className="whitespace-pre-wrap">
                                            {m.content || (m.role === 'assistant' && loading ? '…' : '')}
                                        </div>
                                    </div>
                                    {m.role === 'user' && (
                                        <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted">
                                            <User className="size-4 text-muted-foreground" />
                                        </div>
                                    )}
                                </div>
                            ))}
                            {loading && messages[messages.length - 1]?.role === 'user' && (
                                <div className="flex gap-3">
                                    <div className="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <Bot className="size-4 text-primary" />
                                    </div>
                                    <div className="rounded-lg border bg-background px-4 py-2 text-sm text-muted-foreground">
                                        Thinking…
                                    </div>
                                </div>
                            )}
                            <div ref={messagesEndRef} />
                        </div>

                        {error && (
                            <div className="border-t bg-destructive/10 px-4 py-2 text-sm text-destructive">
                                {error}
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="flex gap-2 border-t p-4">
                            <input
                                type="text"
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                placeholder="Type your message…"
                                className="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                disabled={loading}
                            />
                            <Button type="submit" size="icon" disabled={loading || !input.trim()}>
                                <Send className="size-4" />
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
