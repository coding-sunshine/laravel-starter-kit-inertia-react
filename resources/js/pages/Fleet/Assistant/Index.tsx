import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Bot, MessageSquarePlus, Pencil, Send, Trash2, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
    insights?: string[];
    suggested_questions?: string[];
    context?: string | null;
    context_prompt?: string | null;
}

export default function FleetAssistantIndex({
    conversations = [],
    initial_messages = [],
    conversation_id = null,
    insights = [],
    suggested_questions = [],
    context = null,
    context_prompt = null,
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
    const [editingId, setEditingId] = useState<string | null>(null);
    const [editTitle, setEditTitle] = useState('');
    const [localConversations, setLocalConversations] = useState<ConversationItem[]>(conversations);
    const [deleteTarget, setDeleteTarget] = useState<ConversationItem | null>(null);
    const [deleting, setDeleting] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const abortRef = useRef<AbortController | null>(null);

    // Pre-fill from ?prompt= or from server-provided context_prompt (e.g. ?context=work_order:123)
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const prompt = params.get('prompt');
        if (typeof context_prompt === 'string' && context_prompt !== '') {
            setInput(context_prompt);
        } else if (prompt && typeof prompt === 'string') {
            setInput(prompt);
            params.delete('prompt');
            const q = params.toString();
            window.history.replaceState({}, '', window.location.pathname + (q ? `?${q}` : ''));
        }
    }, [context_prompt]);

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
        setLocalConversations(conversations);
    }, [conversations]);

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
                                const autoTitle = text.slice(0, 60) || 'Fleet Assistant';
                                setLocalConversations((prev) => [
                                    {
                                        id: chunk.conversationId!,
                                        title: autoTitle,
                                        updated_at: new Date().toISOString(),
                                    },
                                    ...prev,
                                ]);
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
        setEditingId(null);
        if (id) {
            router.get('/fleet/assistant', { conversation_id: id });
        } else {
            router.get('/fleet/assistant');
        }
    };

    const startEditing = (c: ConversationItem) => {
        setEditingId(c.id);
        setEditTitle(c.title);
    };

    const cancelEditing = () => {
        setEditingId(null);
        setEditTitle('');
    };

    const submitRename = async () => {
        if (!editingId || !editTitle.trim()) {
            cancelEditing();
            return;
        }
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        const res = await fetch(`/fleet/assistant/conversations/${editingId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ title: editTitle.trim() }),
        });
        if (res.ok) {
            setLocalConversations((prev) =>
                prev.map((x) => (x.id === editingId ? { ...x, title: editTitle.trim() } : x)),
            );
            cancelEditing();
        } else {
            const data = await res.json().catch(() => ({}));
            setError(data?.message ?? 'Failed to rename');
        }
    };

    const performDelete = async (id: string) => {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
        setDeleting(true);
        try {
            const res = await fetch(`/fleet/assistant/conversations/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (res.ok) {
                setLocalConversations((prev) => prev.filter((c) => c.id !== id));
                setDeleteTarget(null);
                if (conversationId === id) {
                    router.visit('/fleet/assistant');
                }
            } else {
                const data = await res.json().catch(() => ({}));
                setError(data?.message ?? 'Failed to delete');
            }
        } finally {
            setDeleting(false);
        }
    };

    const handleDeleteConfirm = () => {
        if (deleteTarget) void performDelete(deleteTarget.id);
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
                        {localConversations.length === 0 && (
                            <p className="px-2 py-4 text-center text-xs text-muted-foreground">No past chats</p>
                        )}
                        {localConversations.map((c) => (
                            <div
                                key={c.id}
                                className={`group flex flex-col gap-0.5 rounded-md px-2 py-2 ${
                                    conversation_id === c.id ? 'bg-muted font-medium' : 'hover:bg-muted/70'
                                }`}
                            >
                                {editingId === c.id ? (
                                    <div className="flex flex-col gap-1" onClick={(e) => e.stopPropagation()}>
                                        <input
                                            type="text"
                                            value={editTitle}
                                            onChange={(e) => setEditTitle(e.target.value)}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') void submitRename();
                                                if (e.key === 'Escape') cancelEditing();
                                            }}
                                            className="w-full rounded border border-input bg-background px-2 py-1 text-sm"
                                            autoFocus
                                        />
                                        <div className="flex gap-1">
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                className="h-7 flex-1 text-xs"
                                                onClick={() => void submitRename()}
                                            >
                                                Save
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="h-7 flex-1 text-xs"
                                                onClick={cancelEditing}
                                            >
                                                Cancel
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <>
                                        <div className="flex min-w-0 items-start gap-1">
                                            <button
                                                type="button"
                                                onClick={() => openConversation(c.id)}
                                                className="min-w-0 flex-1 truncate text-left text-sm"
                                                title={c.title}
                                            >
                                                <span className="block truncate">{c.title}</span>
                                            </button>
                                            <span className="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-6"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        startEditing(c);
                                                    }}
                                                    title="Rename"
                                                >
                                                    <Pencil className="size-3" />
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-6 text-destructive hover:text-destructive"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        setDeleteTarget(c);
                                                    }}
                                                    title="Delete"
                                                >
                                                    <Trash2 className="size-3" />
                                                </Button>
                                            </span>
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {new Date(c.updated_at).toLocaleDateString()}
                                        </span>
                                    </>
                                )}
                            </div>
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
                                <div className="flex flex-col items-center justify-center gap-4 py-8 text-center">
                                    <Bot className="size-12 text-muted-foreground" />
                                    <p className="text-muted-foreground">Send a message to get started.</p>
                                    {insights.length > 0 && (
                                        <div className="w-full max-w-md rounded-lg border bg-card px-4 py-3 text-left text-sm">
                                            <p className="mb-2 font-medium text-foreground">Insights</p>
                                            <ul className="list-inside list-disc space-y-0.5 text-muted-foreground">
                                                {insights.map((line, i) => (
                                                    <li key={i}>{line}</li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}
                                    {suggested_questions.length > 0 && (
                                        <div className="flex flex-wrap justify-center gap-2">
                                            {suggested_questions.map((q, i) => (
                                                <button
                                                    key={i}
                                                    type="button"
                                                    onClick={() => setInput(q)}
                                                    className="rounded-full border bg-muted/50 px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                >
                                                    {q}
                                                </button>
                                            ))}
                                        </div>
                                    )}
                                    {suggested_questions.length === 0 && (
                                        <p className="text-sm text-muted-foreground">
                                            e.g. &quot;List my vehicles&quot; or &quot;Any active alerts?&quot;
                                        </p>
                                    )}
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

            <Dialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete conversation</DialogTitle>
                        <DialogDescription>
                            This will permanently delete &ldquo;{deleteTarget?.title}&rdquo; and all its
                            messages. This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline" disabled={deleting}>
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            onClick={handleDeleteConfirm}
                            disabled={deleting}
                        >
                            {deleting ? 'Deleting…' : 'Delete'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
