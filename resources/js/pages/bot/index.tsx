import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { Bot, Send } from 'lucide-react';
import { useRef, useState } from 'react';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'AI', href: '/ai/bot' },
    { title: 'Bot In A Box v2', href: '/ai/bot' },
];

export default function BotIndexPage() {
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [loading, setLoading] = useState(false);
    const [conversationId, setConversationId] = useState<string | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    const sendMessage = () => {
        const message = input.trim();
        if (!message || loading) return;

        setMessages((prev) => [...prev, { role: 'user', content: message }]);
        setInput('');
        setLoading(true);

        router.post(
            '/ai/bot/chat',
            { message, conversation_id: conversationId },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    const data = page.props as Record<string, unknown>;
                    if (data.flash && typeof data.flash === 'object') {
                        const flash = data.flash as Record<string, unknown>;
                        const reply = (flash.reply as string) ?? '';
                        const newConvId = (flash.conversation_id as string) ?? conversationId;
                        setConversationId(newConvId);
                        setMessages((prev) => [
                            ...prev,
                            { role: 'assistant', content: reply || 'Sorry, I could not process that.' },
                        ]);
                    }
                    setLoading(false);
                    setTimeout(scrollToBottom, 100);
                },
                onError: () => {
                    setMessages((prev) => [
                        ...prev,
                        { role: 'assistant', content: 'An error occurred. Please try again.' },
                    ]);
                    setLoading(false);
                },
            },
        );
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    return (
        <AppSidebarLayout breadcrumbs={breadcrumbs}>
            <Head title="Bot In A Box v2" />
            <div
                className="flex h-full flex-1 flex-col"
                data-pan="ai-bot-tab"
            >
                {/* Header */}
                <div className="border-b bg-white px-6 py-4">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100">
                            <Bot className="size-5 text-indigo-600" />
                        </div>
                        <div>
                            <h1 className="text-lg font-semibold text-gray-900">Bot In A Box v2</h1>
                            <p className="text-sm text-gray-500">
                                Comprehensive CRM assistant — contacts, properties, tasks, pipeline
                            </p>
                        </div>
                    </div>
                </div>

                {/* Messages */}
                <div className="flex-1 overflow-y-auto p-6 space-y-4">
                    {messages.length === 0 && (
                        <div className="flex flex-col items-center justify-center gap-4 py-16 text-center">
                            <div className="rounded-full bg-indigo-50 p-4">
                                <Bot className="size-8 text-indigo-400" />
                            </div>
                            <div>
                                <p className="font-medium text-gray-900">How can I help you today?</p>
                                <p className="mt-1 text-sm text-gray-500">
                                    Ask me about contacts, properties, tasks, or your pipeline.
                                </p>
                            </div>
                            <div className="grid grid-cols-2 gap-2 max-w-md mt-2">
                                {[
                                    'Show me hot leads',
                                    'Find available lots under $500k',
                                    'What are my tasks today?',
                                    'Show pipeline summary',
                                ].map((suggestion) => (
                                    <button
                                        key={suggestion}
                                        onClick={() => setInput(suggestion)}
                                        className="rounded-lg border border-gray-200 px-3 py-2 text-left text-sm text-gray-600 hover:border-indigo-300 hover:bg-indigo-50 transition-colors"
                                    >
                                        {suggestion}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}

                    {messages.map((message, index) => (
                        <div
                            key={index}
                            className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                        >
                            <div
                                className={`max-w-2xl rounded-2xl px-4 py-3 text-sm ${
                                    message.role === 'user'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-900'
                                }`}
                            >
                                <p className="whitespace-pre-wrap">{message.content}</p>
                            </div>
                        </div>
                    ))}

                    {loading && (
                        <div className="flex justify-start">
                            <div className="max-w-2xl rounded-2xl bg-gray-100 px-4 py-3">
                                <div className="flex gap-1">
                                    <span className="size-2 rounded-full bg-gray-400 animate-bounce [animation-delay:0ms]" />
                                    <span className="size-2 rounded-full bg-gray-400 animate-bounce [animation-delay:150ms]" />
                                    <span className="size-2 rounded-full bg-gray-400 animate-bounce [animation-delay:300ms]" />
                                </div>
                            </div>
                        </div>
                    )}

                    <div ref={messagesEndRef} />
                </div>

                {/* Input */}
                <div className="border-t bg-white px-6 py-4">
                    <div className="flex items-end gap-3">
                        <textarea
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            onKeyDown={handleKeyDown}
                            placeholder="Ask about contacts, properties, tasks..."
                            rows={1}
                            className="flex-1 resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        />
                        <button
                            onClick={sendMessage}
                            disabled={!input.trim() || loading}
                            className="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white transition-colors hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Send className="size-4" />
                        </button>
                    </div>
                </div>
            </div>
        </AppSidebarLayout>
    );
}
