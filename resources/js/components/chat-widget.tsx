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
    AlertTriangle,
    BarChart3,
    Bot,
    Calculator,
    ClipboardList,
    FileText,
    HelpCircle,
    LayoutDashboard,
    List,
    MessageCircle,
    MessageSquare,
    MessageSquarePlus,
    Search,
    Send,
    Sparkles,
    Train,
    Truck,
} from 'lucide-react';
import {
    type ElementType,
    useCallback,
    useEffect,
    useRef,
    useState,
} from 'react';

interface ChatMessage {
    role: 'user' | 'assistant';
    content: string;
}

interface ConversationSummary {
    id: string;
    title: string;
    updated_at: string;
}

interface SuggestedQuestion {
    icon: ElementType;
    text: string;
}

interface DemurrageWarning {
    rake_number: string;
    siding_name: string;
    remaining_minutes: number;
}

const CHAT_URL = '/chat';
const CONVERSATIONS_URL = '/chat/conversations';
const DEMURRAGE_WARNING_URL = '/chat/demurrage-warnings';

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
        '/dashboard': 'Dashboard',
        '/rakes': 'Rakes',
        '/indents': 'Indents',
        '/penalties': 'Penalties',
        '/railway-receipts': 'Railway Receipts',
        '/road-dispatch/arrivals': 'Road Dispatch Arrivals',
        '/road-dispatch/unloads': 'Road Dispatch Unloads',
        '/reconciliation': 'Reconciliation',
        '/alerts': 'Alerts',
        '/reports': 'Reports',
    };
    // Exact match first
    if (labels[path]) return labels[path];
    // Prefix match for sub-pages (e.g. /rakes/123)
    for (const [prefix, label] of Object.entries(labels)) {
        if (path.startsWith(prefix + '/')) return label;
    }
    return path;
}

function getPageKey(): string {
    if (typeof window === 'undefined') return 'default';
    const path = window.location.pathname;
    const prefixes: [string, string][] = [
        ['/dashboard', 'dashboard'],
        ['/rakes', 'rakes'],
        ['/indents', 'indents'],
        ['/penalties', 'penalties'],
        ['/railway-receipts', 'railway-receipts'],
        ['/road-dispatch/arrivals', 'arrivals'],
        ['/road-dispatch/unloads', 'unloads'],
        ['/reconciliation', 'reconciliation'],
        ['/alerts', 'alerts'],
        ['/reports', 'reports'],
    ];
    for (const [prefix, key] of prefixes) {
        if (path === prefix || path.startsWith(prefix + '/')) return key;
    }
    return 'default';
}

const SUGGESTED_QUESTIONS: Record<string, SuggestedQuestion[]> = {
    dashboard: [
        {
            icon: LayoutDashboard,
            text: 'Give me a summary of today\u2019s operations',
        },
        { icon: ClipboardList, text: 'What indents are pending placement?' },
        {
            icon: AlertTriangle,
            text: 'Are there any active alerts I should know about?',
        },
        {
            icon: HelpCircle,
            text: 'How does RRMCS differ from managing this in Excel?',
        },
    ],
    rakes: [
        { icon: Calculator, text: 'How is demurrage calculated for a rake?' },
        { icon: Train, text: 'What rakes are currently in transit?' },
        {
            icon: Search,
            text: 'Explain the rake lifecycle from indent to unloading',
        },
        {
            icon: HelpCircle,
            text: 'How does tracking rakes here differ from an Excel stopwatch?',
        },
    ],
    indents: [
        { icon: FileText, text: 'How do I create a new e-Demand indent?' },
        {
            icon: HelpCircle,
            text: 'What is the difference between an indent and an e-Demand?',
        },
        {
            icon: ClipboardList,
            text: 'Show me pending indents awaiting placement',
        },
        {
            icon: HelpCircle,
            text: 'How does this replace the paper indent register?',
        },
    ],
    penalties: [
        { icon: Calculator, text: 'How are penalty charges calculated?' },
        {
            icon: AlertTriangle,
            text: 'What are the most common causes of penalties?',
        },
        { icon: FileText, text: 'How do I dispute a penalty?' },
        {
            icon: HelpCircle,
            text: 'How does this differ from the Excel penalty worksheet?',
        },
    ],
    'railway-receipts': [
        { icon: FileText, text: 'How do I file a new railway receipt?' },
        { icon: ClipboardList, text: 'What documents are required for an RR?' },
        { icon: Search, text: 'How do I check the status of an existing RR?' },
        {
            icon: HelpCircle,
            text: 'How does this differ from manual RR filing?',
        },
    ],
    arrivals: [
        { icon: Truck, text: 'How do I log a new road dispatch arrival?' },
        { icon: ClipboardList, text: 'What vehicles are expected today?' },
        { icon: Calculator, text: 'How is weighment recorded for arrivals?' },
        {
            icon: HelpCircle,
            text: 'How does this differ from the gate register?',
        },
    ],
    unloads: [
        { icon: Truck, text: 'How do I record an unload?' },
        { icon: ClipboardList, text: 'What unloads are pending completion?' },
        {
            icon: Calculator,
            text: 'How is stock quantity calculated after unloading?',
        },
        { icon: HelpCircle, text: 'How does this differ from paper challans?' },
    ],
    reconciliation: [
        { icon: Search, text: 'How does the reconciliation process work?' },
        {
            icon: AlertTriangle,
            text: 'What should I do when there\u2019s a discrepancy?',
        },
        { icon: BarChart3, text: 'How are power plant quantities matched?' },
        {
            icon: HelpCircle,
            text: 'How does this differ from VLOOKUP reconciliation in Excel?',
        },
    ],
    alerts: [
        { icon: AlertTriangle, text: 'What active alerts need my attention?' },
        {
            icon: ClipboardList,
            text: 'How do I configure notification preferences?',
        },
        { icon: Search, text: 'What types of alerts does RRMCS support?' },
        {
            icon: HelpCircle,
            text: 'How does this differ from manual tracking and reminders?',
        },
    ],
    reports: [
        { icon: BarChart3, text: 'What types of reports are available?' },
        { icon: FileText, text: 'How do I generate a demurrage report?' },
        {
            icon: ClipboardList,
            text: 'Can reports be scheduled automatically?',
        },
        {
            icon: HelpCircle,
            text: 'How do these reports differ from Excel reports?',
        },
    ],
    default: [
        { icon: Sparkles, text: 'What does RRMCS do and how can it help me?' },
        { icon: Calculator, text: 'How is demurrage calculated in RRMCS?' },
        {
            icon: LayoutDashboard,
            text: 'Give me an overview of operations today',
        },
        {
            icon: HelpCircle,
            text: 'How does RRMCS differ from managing operations in Excel?',
        },
    ],
};

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
    const [mobileView, setMobileView] = useState<'chat' | 'conversations'>(
        'chat',
    );
    const [demurrageWarnings, setDemurrageWarnings] = useState<
        DemurrageWarning[]
    >([]);
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

    // Fetch demurrage warnings when opening chat on /rakes page
    useEffect(() => {
        if (!open) return;
        const pageKey = getPageKey();
        if (pageKey !== 'rakes' && pageKey !== 'dashboard') {
            setDemurrageWarnings([]);
            return;
        }
        (async () => {
            try {
                const res = await fetch(DEMURRAGE_WARNING_URL, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (res.ok) {
                    const data = await res.json();
                    setDemurrageWarnings(data.warnings ?? []);
                }
            } catch {
                setDemurrageWarnings([]);
            }
        })();
    }, [open]);

    const startNewChat = useCallback(() => {
        setMessages([]);
        setConversationId(null);
        setError(null);
        setMobileView('chat');
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
                setMobileView('chat');
                scrollToBottom();
            } catch {
                setError('Failed to load conversation.');
            }
        },
        [scrollToBottom],
    );

    const sendMessage = useCallback(
        async (overrideText?: string) => {
            const text = (overrideText ?? input).trim();
            if (!text || loading) return;

            if (!overrideText) setInput('');
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
        },
        [input, loading, conversationId, scrollToBottom, fetchConversations],
    );

    // Listen for external chat triggers (e.g. "Ask AI about this penalty")
    useEffect(() => {
        const handler = (e: Event) => {
            const detail = (e as CustomEvent<{ message: string }>).detail;
            if (detail?.message) {
                startNewChat();
                setOpen(true);
                // Small delay to ensure widget is open before sending
                setTimeout(() => sendMessage(detail.message), 150);
            }
        };
        window.addEventListener('chat:ask', handler);
        return () => window.removeEventListener('chat:ask', handler);
    }, [startNewChat, sendMessage]);

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

    const pageKey = getPageKey();
    const questions =
        SUGGESTED_QUESTIONS[pageKey] ?? SUGGESTED_QUESTIONS.default;

    const conversationsSidebar = (
        <div className="flex h-full flex-col">
            <div className="flex-shrink-0 p-3">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={startNewChat}
                    className="w-full gap-1.5"
                    data-pan="chat-new-chat"
                >
                    <MessageSquarePlus className="size-4" />
                    New chat
                </Button>
            </div>
            <div className="flex-1 overflow-y-auto px-2 pb-3">
                {conversationsLoading ? (
                    <div className="flex items-center justify-center py-6 text-xs text-muted-foreground">
                        Loading…
                    </div>
                ) : conversations.length === 0 ? (
                    <p className="py-4 text-center text-xs text-muted-foreground">
                        No conversations yet.
                    </p>
                ) : (
                    <div className="space-y-0.5">
                        {conversations.map((c) => (
                            <button
                                key={c.id}
                                type="button"
                                onClick={() => loadConversation(c.id)}
                                className={cn(
                                    'w-full rounded-lg px-2.5 py-2 text-left text-xs transition-colors',
                                    conversationId === c.id
                                        ? 'bg-primary/10 font-medium text-foreground'
                                        : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                                )}
                                title={c.title}
                                data-pan="chat-conversation-item"
                            >
                                <span className="block truncate">
                                    {c.title || 'Chat'}
                                </span>
                                <span className="mt-0.5 block text-[10px] opacity-70">
                                    {formatConversationDate(c.updated_at)}
                                </span>
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );

    const chatPanel = (
        <div className="flex min-h-0 flex-1 flex-col">
            {/* Proactive demurrage warnings */}
            {demurrageWarnings.length > 0 && messages.length === 0 && (
                <div className="flex-shrink-0 border-b border-amber-200 bg-amber-50 px-4 py-2.5 dark:border-amber-800 dark:bg-amber-950/30">
                    {demurrageWarnings.map((w) => (
                        <button
                            key={w.rake_number}
                            type="button"
                            onClick={() =>
                                sendMessage(
                                    `Rake ${w.rake_number} at ${w.siding_name} has only ${w.remaining_minutes} minutes of free time left. What can we do to avoid demurrage penalties?`,
                                )
                            }
                            className="flex w-full items-start gap-2 rounded px-1 py-1 text-left text-xs text-amber-800 transition-colors hover:bg-amber-100 dark:text-amber-300 dark:hover:bg-amber-900/40"
                        >
                            <AlertTriangle className="mt-0.5 size-3.5 shrink-0" />
                            <span>
                                <strong>{w.rake_number}</strong> has{' '}
                                {w.remaining_minutes} min of free time left at{' '}
                                {w.siding_name} — tap to ask AI for help
                            </span>
                        </button>
                    ))}
                </div>
            )}
            <div className="flex-1 overflow-y-auto px-4 py-4">
                <div className="mx-auto max-w-2xl space-y-5">
                    {messages.length === 0 && (
                        <div className="flex flex-col items-center justify-center py-8 text-center">
                            <div className="mb-4 flex size-12 items-center justify-center rounded-2xl bg-primary/5">
                                <Sparkles className="size-6 text-primary/70" />
                            </div>
                            <p className="text-sm font-medium text-foreground">
                                Ask anything about RRMCS
                            </p>
                            <p className="mt-1 max-w-[260px] text-xs leading-relaxed text-muted-foreground">
                                Rakes, indents, sidings, demurrage — I can use
                                your current data to answer.
                            </p>
                            <div className="mt-6 grid w-full max-w-md grid-cols-2 gap-2.5">
                                {questions.map((q) => {
                                    const Icon = q.icon;
                                    return (
                                        <button
                                            key={q.text}
                                            type="button"
                                            onClick={() => sendMessage(q.text)}
                                            className="group flex items-start gap-2.5 rounded-xl border border-border/60 bg-muted/20 px-3 py-2.5 text-left text-xs leading-snug text-muted-foreground transition-colors hover:border-primary/30 hover:bg-primary/5 hover:text-foreground"
                                            data-pan="chat-suggested-question"
                                        >
                                            <Icon className="mt-0.5 size-3.5 shrink-0 text-primary/50 transition-colors group-hover:text-primary/80" />
                                            <span className="line-clamp-3">
                                                {q.text}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
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
                        onClick={() => sendMessage()}
                        disabled={loading || !input.trim()}
                        aria-label="Send"
                        data-pan="chat-send"
                    >
                        <Send className="size-5" />
                    </Button>
                </div>
            </div>
        </div>
    );

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
                className="flex h-full w-full flex-col gap-0 border-l bg-background p-0 sm:max-w-2xl"
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
                        {/* Mobile toggle - visible only below sm */}
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                setMobileView((v) =>
                                    v === 'chat' ? 'conversations' : 'chat',
                                )
                            }
                            className="shrink-0 gap-1.5 text-muted-foreground hover:text-foreground sm:hidden"
                            aria-label={
                                mobileView === 'chat'
                                    ? 'Show conversations'
                                    : 'Show chat'
                            }
                        >
                            {mobileView === 'chat' ? (
                                <List className="size-4" />
                            ) : (
                                <MessageSquare className="size-4" />
                            )}
                        </Button>
                    </div>
                </SheetHeader>

                {/* 2-panel body */}
                <div className="flex min-h-0 flex-1">
                    {/* Left sidebar - always visible on sm+, toggle on mobile */}
                    <div
                        className={cn(
                            'flex-shrink-0 border-r border-border bg-muted/30',
                            'sm:flex sm:w-52 sm:flex-col',
                            mobileView === 'conversations'
                                ? 'flex w-full flex-col'
                                : 'hidden',
                        )}
                    >
                        {conversationsSidebar}
                    </div>

                    {/* Right chat panel - always visible on sm+, toggle on mobile */}
                    <div
                        className={cn(
                            'flex min-w-0 flex-1 flex-col',
                            'sm:flex',
                            mobileView === 'chat' ? 'flex' : 'hidden',
                        )}
                    >
                        {chatPanel}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
