import { useEffect, useRef, useState } from 'react';
import { Bot, Send, Sparkles, X } from 'lucide-react';

interface Message {
    role: 'user' | 'assistant';
    content: string;
    html?: boolean;
}

interface AiChatPanelProps {
    open: boolean;
    onClose: () => void;
}

export function AiChatPanel({ open, onClose }: AiChatPanelProps) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [conversationId, setConversationId] = useState<string | null>(null);
    const bottomRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLTextAreaElement>(null);

    useEffect(() => {
        if (open) {
            setTimeout(() => inputRef.current?.focus(), 300);
        }
    }, [open]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const sendMessage = async () => {
        const query = input.trim();
        if (!query || loading) return;

        setInput('');
        setMessages((prev) => [...prev, { role: 'user', content: query }]);
        setLoading(true);

        try {
            const csrfToken =
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content ?? '';

            const xsrfToken = document.cookie
                .split('; ')
                .find((c) => c.startsWith('XSRF-TOKEN='))
                ?.split('=')[1];

            const res = await fetch('/ai/concierge/match', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    ...(xsrfToken
                        ? {
                              'X-XSRF-TOKEN': decodeURIComponent(xsrfToken),
                          }
                        : {}),
                },
                body: JSON.stringify({
                    message: query,
                    conversation_id: conversationId,
                }),
            });

            if (res.ok) {
                const data = await res.json();
                const reply =
                    data.reply ??
                    data.flash?.reply ??
                    'Sorry, I could not process that request.';
                const isHtml = data.html === true;
                if (data.conversation_id) {
                    setConversationId(data.conversation_id);
                }
                setMessages((prev) => [
                    ...prev,
                    { role: 'assistant', content: reply, html: isHtml },
                ]);
            } else {
                setMessages((prev) => [
                    ...prev,
                    {
                        role: 'assistant',
                        content:
                            'Sorry, something went wrong. Please try again.',
                    },
                ]);
            }
        } catch {
            setMessages((prev) => [
                ...prev,
                {
                    role: 'assistant',
                    content:
                        'Network error. Please check your connection and try again.',
                },
            ]);
        } finally {
            setLoading(false);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    return (
        <>
            {/* Backdrop */}
            {open && (
                <div
                    className="fixed inset-0 z-50 bg-black/20 transition-opacity"
                    onClick={onClose}
                />
            )}

            {/* Panel */}
            <div
                className={`fixed right-0 top-0 z-50 flex h-full w-full max-w-md flex-col border-l bg-background shadow-2xl transition-transform duration-300 ${
                    open ? 'translate-x-0' : 'translate-x-full'
                }`}
            >
                {/* Header */}
                <div className="flex items-center justify-between border-b px-4 py-3">
                    <div className="flex items-center gap-2.5">
                        <div className="flex size-8 items-center justify-center rounded-full bg-primary/10">
                            <Bot className="size-4 text-primary" />
                        </div>
                        <div>
                            <h2 className="text-sm font-semibold">
                                Fusion AI Assistant
                            </h2>
                            <p className="text-[11px] text-muted-foreground">
                                Ask about properties, contacts, or anything
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    >
                        <X className="size-4" />
                    </button>
                </div>

                {/* Messages */}
                <div className="flex-1 overflow-y-auto p-4">
                    {messages.length === 0 && (
                        <div className="flex flex-col items-center justify-center gap-3 py-16 text-center">
                            <div className="rounded-full bg-primary/10 p-3">
                                <Sparkles className="size-6 text-primary" />
                            </div>
                            <div>
                                <p className="text-sm font-medium">
                                    How can I help?
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Ask me anything about your CRM data
                                </p>
                            </div>
                            <div className="mt-2 flex flex-col gap-1.5">
                                {[
                                    'Find properties in Sydney under $800k',
                                    'Show me hot leads with high scores',
                                    'What projects have available lots?',
                                    'Match buyer John to suitable properties',
                                ].map((suggestion) => (
                                    <button
                                        key={suggestion}
                                        onClick={() => {
                                            setInput(suggestion);
                                            setTimeout(
                                                () =>
                                                    inputRef.current?.focus(),
                                                0,
                                            );
                                        }}
                                        className="rounded-lg border px-3 py-2 text-left text-xs text-muted-foreground transition-colors hover:border-primary/30 hover:bg-primary/5 hover:text-foreground"
                                    >
                                        {suggestion}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="space-y-4">
                        {messages.map((msg, i) => (
                            <div
                                key={i}
                                className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                            >
                                <div
                                    className={`max-w-[85%] rounded-xl px-3.5 py-2.5 text-sm ${
                                        msg.role === 'user'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted'
                                    }`}
                                >
                                    {msg.html ? (
                                        <div
                                            className="prose prose-sm max-w-none dark:prose-invert [&_table]:text-xs [&_th]:px-2 [&_td]:px-2"
                                            dangerouslySetInnerHTML={{
                                                __html: msg.content,
                                            }}
                                        />
                                    ) : (
                                        <p className="whitespace-pre-wrap leading-relaxed">
                                            {msg.content}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}

                        {loading && (
                            <div className="flex justify-start">
                                <div className="flex items-center gap-1.5 rounded-xl bg-muted px-4 py-3">
                                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/40 [animation-delay:0ms]" />
                                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/40 [animation-delay:150ms]" />
                                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/40 [animation-delay:300ms]" />
                                </div>
                            </div>
                        )}
                        <div ref={bottomRef} />
                    </div>
                </div>

                {/* Input */}
                <div className="border-t p-3">
                    <div className="flex items-end gap-2 rounded-xl border bg-muted/30 px-3 py-2 focus-within:border-primary/40 focus-within:ring-1 focus-within:ring-primary/20">
                        <textarea
                            ref={inputRef}
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={handleKeyDown}
                            placeholder="Ask about properties, contacts..."
                            rows={1}
                            className="flex-1 resize-none bg-transparent text-sm outline-none placeholder:text-muted-foreground/60"
                            style={{
                                minHeight: '24px',
                                maxHeight: '120px',
                            }}
                        />
                        <button
                            onClick={sendMessage}
                            disabled={!input.trim() || loading}
                            className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-40"
                        >
                            <Send className="size-3.5" />
                        </button>
                    </div>
                    <p className="mt-1.5 text-center text-[10px] text-muted-foreground/50">
                        AI responses are generated and may not be 100% accurate
                    </p>
                </div>
            </div>
        </>
    );
}
