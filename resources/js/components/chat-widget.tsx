'use client';

import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import {
    Bot,
    MessageCircle,
    MessageSquarePlus,
    Send,
    Sparkles,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
}

interface ConversationSummary {
    id: string;
    title: string;
    updated_at: string;
}

const CHAT_URL = '/chat';
const CONVERSATIONS_URL = '/chat/conversations';

/**
 * CSRF token for request headers. Prefers XSRF-TOKEN cookie (stays current after
 * Inertia navigations); falls back to meta tag. Use with headers below.
 */
function getCsrfHeaders(): Record<string, string> {
    const cookieMatch = document.cookie.match(/\bXSRF-TOKEN=([^;]+)/);
    if (cookieMatch) {
        return { 'X-XSRF-TOKEN': decodeURIComponent(cookieMatch[1].trim()) };
    }
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = meta?.getAttribute('content') ?? '';
    return token ? { 'X-CSRF-TOKEN': token } : {};
}

function getCurrentPage(): string {
    if (typeof window === 'undefined') return '';
    const path = window.location.pathname;
    const labels: Record<string, string> = {
        '/rakes': 'Rakes',
        '/indents': 'Indents',
        '/dashboard': 'Dashboard',
    };
    return labels[path] ?? path;
}

function TypingIndicator() {
    return (
        <div className="mr-auto flex max-w-[85%] items-center gap-1.5 rounded-2xl border border-border/80 bg-muted/40 px-4 py-3 shadow-sm">
            <span className="flex gap-1">
                {[0, 1, 2].map((i) => (
                    <span
                        key={i}
                        className="size-2 animate-bounce rounded-full bg-muted-foreground/60"
                        style={{ animationDelay: `${i * 0.15}s` }}
                    />
                ))}
            </span>
        </div>
    );
}

function formatConversationDate(updatedAt: string): string {
    try {
        const d = new Date(updatedAt);
        const now = new Date();
        const sameDay = d.toDateString() === now.toDateString();
        return sameDay
            ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            : d.toLocaleDateString();
    } catch {
        return '';
    }
}

export function ChatWidget() {
    const [open, setOpen] = useState(false);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [conversationId, setConversationId] = useState<string | null>(null);
    const [conversations, setConversations] = useState<ConversationSummary[]>(
        [],
    );
    const [conversationsLoading, setConversationsLoading] = useState(false);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    const scrollToBottom = useCallback(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, []);

    const fetchConversations = useCallback(async () => {
        setConversationsLoading(true);
        try {
            const res = await fetch(CONVERSATIONS_URL, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (res.ok && data.conversations) {
                setConversations(data.conversations);
            }
        } catch {
            setConversations([]);
        } finally {
            setConversationsLoading(false);
        }
    }, []);

    useEffect(() => {
        if (open) {
            fetchConversations();
        }
    }, [open, fetchConversations]);

    const startNewChat = useCallback(() => {
        setMessages([]);
        setConversationId(null);
        setError(null);
    }, []);

    const loadConversation = useCallback(
        async (id: string) => {
            setError(null);
            try {
                const res = await fetch(`/chat/conversations/${id}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                if (!res.ok) {
                    setError(data?.error ?? 'Could not load conversation.');
                    return;
                }
                setConversationId(data.conversation_id);
                setMessages(
                    (data.messages ?? []).map(
                        (m: { role: string; content: string }) => ({
                            role: m.role as 'user' | 'assistant',
                            content: m.content ?? '',
                        }),
                    ),
                );
                scrollToBottom();
            } catch {
                setError('Failed to load conversation.');
            }
        },
        [scrollToBottom],
    );

    const sendMessage = useCallback(async () => {
        const text = input.trim();
        if (!text || loading) return;

        setInput('');
        setError(null);
        const userMessage: ChatMessage = { role: 'user', content: text };
        setMessages((prev) => [...prev, userMessage]);
        setLoading(true);
        scrollToBottom();
        if (textareaRef.current) {
            textareaRef.current.style.height = 'auto';
        }

        try {
            const response = await fetch(CHAT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...getCsrfHeaders(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    message: text,
                    conversation_id: conversationId,
                    current_page: getCurrentPage(),
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                const message =
                    data?.error ??
                    (response.status === 419
                        ? 'Session may have expired. Please refresh the page and try again.'
                        : 'Something went wrong.');
                const detail = data?.debug_error
                    ? ` (${data.debug_error})`
                    : '';
                setError(message + detail);
                return;
            }

            if (data.conversation_id) {
                setConversationId(data.conversation_id);
                fetchConversations();
            }
            if (data.message?.content) {
                setMessages((prev) => [
                    ...prev,
                    { role: 'assistant', content: data.message.content },
                ]);
                scrollToBottom();
            }
        } catch {
            setError('Failed to send message. Please try again.');
        } finally {
            setLoading(false);
        }
    }, [input, loading, conversationId, scrollToBottom, fetchConversations]);

    const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    const handleTextareaChange = (
        e: React.ChangeEvent<HTMLTextAreaElement>,
    ) => {
        setInput(e.target.value);
        const el = e.target;
        el.style.height = 'auto';
        el.style.height = `${Math.min(el.scrollHeight, 120)}px`;
    };

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <Button
                    variant="default"
                    size="icon"
                    className="fixed right-6 bottom-6 size-14 rounded-full shadow-xl transition-all hover:scale-105 hover:shadow-2xl"
                    aria-label="Open AI chat"
                    data-pan="chat-open"
                >
                    <MessageCircle className="size-7" />
                </Button>
            </SheetTrigger>
            <SheetContent
                side="right"
                className="flex h-full w-full flex-col gap-0 border-l bg-background p-0 sm:max-w-lg"
            >
                {/* Header */}
                <SheetHeader className="flex-shrink-0 border-b border-border bg-background px-4 py-3">
                    <div className="flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2.5">
                            <div className="flex size-9 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Bot className="size-5" />
                            </div>
                            <div>
                                <SheetTitle className="truncate text-base font-semibold tracking-tight">
                                    AI Assistant
                                </SheetTitle>
                                <p className="text-xs text-muted-foreground">
                                    RRMCS helper
                                </p>
                            </div>
                        </div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={startNewChat}
                            className="shrink-0 gap-1.5 text-muted-foreground hover:text-foreground"
                            aria-label="New chat"
                            data-pan="chat-new-chat"
                        >
                            <MessageSquarePlus className="size-4" />
                            <span className="hidden sm:inline">New chat</span>
                        </Button>
                    </div>
                </SheetHeader>

                {/* Conversations strip */}
                <div className="flex-shrink-0 border-b border-border bg-muted px-3 py-2.5">
                    <p className="mb-2 px-1 text-[11px] font-medium tracking-wider text-muted-foreground uppercase">
                        Conversations
                    </p>
                    <div className="flex gap-1.5 overflow-x-auto pb-1">
                        {conversationsLoading ? (
                            <div className="flex items-center justify-center py-4 text-xs text-muted-foreground">
                                Loading…
                            </div>
                        ) : conversations.length === 0 ? (
                            <p className="py-2 text-center text-xs text-muted-foreground">
                                No conversations yet.
                            </p>
                        ) : (
                            conversations.slice(0, 8).map((c) => (
                                <button
                                    key={c.id}
                                    type="button"
                                    onClick={() => loadConversation(c.id)}
                                    className={cn(
                                        'shrink-0 rounded-lg border px-3 py-2 text-left text-xs transition-all',
                                        conversationId === c.id
                                            ? 'border-primary/40 bg-primary/10 font-medium text-foreground shadow-sm'
                                            : 'border-border bg-background text-muted-foreground hover:border-border hover:bg-muted/50 hover:text-foreground',
                                    )}
                                    title={c.title}
                                    data-pan="chat-conversation-item"
                                >
                                    <span className="block max-w-[140px] truncate">
                                        {c.title || 'Chat'}
                                    </span>
                                    <span className="mt-0.5 block text-[10px] opacity-70">
                                        {formatConversationDate(c.updated_at)}
                                    </span>
                                </button>
                            ))
                        )}
                    </div>
                </div>

                {/* Messages */}
                <div className="flex min-h-0 flex-1 flex-col">
                    <div className="flex-1 overflow-y-auto px-4 py-4">
                        <div className="mx-auto max-w-2xl space-y-5">
                            {messages.length === 0 && (
                                <div className="flex flex-col items-center justify-center py-12 text-center">
                                    <div className="mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/5">
                                        <Sparkles className="size-7 text-primary/70" />
                                    </div>
                                    <p className="text-sm font-medium text-foreground">
                                        Ask anything about RRMCS
                                    </p>
                                    <p className="mt-1 max-w-[260px] text-xs leading-relaxed text-muted-foreground">
                                        Rakes, indents, sidings, demurrage — I
                                        can use your current data to answer.
                                    </p>
                                </div>
                            )}
                            {messages.map((msg, i) => (
                                <div
                                    // eslint-disable-next-line @eslint-react/no-array-index-key -- chat messages: role+content+index for stable keys
                                    key={`${msg.role}-${msg.content.slice(0, 80)}-${i}`}
                                    className={cn(
                                        'flex',
                                        msg.role === 'user' && 'justify-end',
                                    )}
                                >
                                    <div
                                        className={cn(
                                            'max-w-[85%] rounded-2xl px-4 py-3 text-sm shadow-sm',
                                            msg.role === 'user'
                                                ? 'bg-primary text-primary-foreground'
                                                : 'border border-border/70 bg-muted/30 text-foreground',
                                        )}
                                    >
                                        <div className="leading-relaxed break-words whitespace-pre-wrap">
                                            {msg.content}
                                        </div>
                                    </div>
                                </div>
                            ))}
                            {loading && (
                                <div className="flex justify-start">
                                    <TypingIndicator />
                                </div>
                            )}
                            <div ref={messagesEndRef} />
                        </div>
                    </div>

                    {/* Error */}
                    {error && (
                        <div className="flex-shrink-0 px-4 pb-2">
                            <div className="rounded-xl border border-destructive/30 bg-destructive/10 px-3 py-2 text-xs text-destructive">
                                {error}
                            </div>
                        </div>
                    )}

                    {/* Input */}
                    <div className="flex-shrink-0 border-t border-border bg-background p-3">
                        <div className="flex gap-2">
                            <textarea
                                ref={textareaRef}
                                placeholder="Type a message…"
                                value={input}
                                onChange={handleTextareaChange}
                                onKeyDown={handleKeyDown}
                                disabled={loading}
                                rows={1}
                                className={cn(
                                    'max-h-[120px] min-h-[44px] w-full resize-none rounded-xl border border-input bg-background px-4 py-3 text-sm shadow-xs transition-[box-shadow,border-color] outline-none',
                                    'placeholder:text-muted-foreground',
                                    'focus:border-ring focus:ring-2 focus:ring-ring/30',
                                    'disabled:cursor-not-allowed disabled:opacity-60',
                                )}
                            />
                            <Button
                                type="button"
                                size="icon"
                                className="size-11 shrink-0 rounded-xl"
                                onClick={sendMessage}
                                disabled={loading || !input.trim()}
                                aria-label="Send"
                                data-pan="chat-send"
                            >
                                <Send className="size-5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
