import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';
import type { UIMessage } from '@tanstack/ai-client';
import { Bot, Check, Copy } from 'lucide-react';
import type { ReactNode } from 'react';
import { useCallback, useRef, useState } from 'react';
import Markdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { toast } from 'sonner';

function getTextContent(message: UIMessage): string {
    return (
        message.parts
            ?.filter(
                (p): p is { type: 'text'; content: string } =>
                    p.type === 'text',
            )
            .map((p) => p.content)
            .join('') ?? ''
    );
}

type ToolInvocationPart = {
    type: 'tool-invocation';
    toolName?: string;
    toolCallId?: string;
};

function getToolInvocationParts(message: UIMessage): ToolInvocationPart[] {
    return (
        message.parts?.filter(
            (p): p is ToolInvocationPart => p.type === 'tool-invocation',
        ) ?? []
    );
}

/** Turn tool name like search_help_articles into "Searched help articles" */
function formatToolLabel(name: string): string {
    if (!name) return 'Used a tool';
    const words = name.replace(/_/g, ' ').toLowerCase().split(' ');
    if (words.length === 0) return name;
    const pastTense = (w: string) => {
        if (w === 'list') return 'Listed';
        if (w === 'search') return 'Searched';
        if (w === 'get') return 'Got';
        if (w === 'create') return 'Created';
        if (w === 'update') return 'Updated';
        return w.charAt(0).toUpperCase() + w.slice(1);
    };
    const first = pastTense(words[0]);
    const rest = words
        .slice(1)
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
    return rest ? `${first} ${rest}` : first;
}

function extractText(node: ReactNode): string {
    if (typeof node === 'string') return node;
    if (typeof node === 'number') return String(node);
    if (!node) return '';
    if (Array.isArray(node)) return node.map(extractText).join('');
    if (typeof node === 'object' && 'props' in node) {
        return extractText(
            (node as { props: { children?: ReactNode } }).props.children,
        );
    }
    return '';
}

function CopyButton({ text, label }: { text: string; label: string }) {
    const [copied, setCopied] = useState(false);
    const timerRef = useRef<ReturnType<typeof setTimeout> | undefined>(
        undefined,
    );

    const handleCopy = useCallback(() => {
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            toast.success('Copied to clipboard');
            if (timerRef.current) clearTimeout(timerRef.current);
            timerRef.current = setTimeout(() => setCopied(false), 2000);
        });
    }, [text]);

    return (
        <button
            type="button"
            onClick={handleCopy}
            className="rounded p-1 text-muted-foreground transition-colors hover:bg-background/80 hover:text-foreground"
            aria-label={label}
            data-pan={
                label === 'Copy message'
                    ? 'chat-copy-message'
                    : 'chat-copy-code'
            }
        >
            {copied ? (
                <Check className="size-3.5" />
            ) : (
                <Copy className="size-3.5" />
            )}
        </button>
    );
}

function CodeBlock({ children, ...props }: React.ComponentProps<'pre'>) {
    const text = extractText(children);

    return (
        <div className="group/code relative">
            <pre {...props}>{children}</pre>
            <div className="absolute top-2 right-2 opacity-0 transition-opacity group-hover/code:opacity-100">
                <CopyButton text={text} label="Copy code" />
            </div>
        </div>
    );
}

export function MessageBubble({
    message,
    user,
}: {
    message: UIMessage;
    user: User;
}) {
    const getInitials = useInitials();
    const content = getTextContent(message);
    const toolParts =
        message.role === 'assistant' ? getToolInvocationParts(message) : [];

    if (message.role === 'user') {
        return (
            <div className="flex items-start justify-end gap-3">
                <div className="max-w-[80%] rounded-2xl rounded-tr-sm bg-primary px-4 py-2.5 text-primary-foreground">
                    <p className="text-sm break-words whitespace-pre-wrap">
                        {content}
                    </p>
                </div>
                <Avatar className="size-7 shrink-0">
                    {user.avatar && (
                        <AvatarImage src={user.avatar} alt={user.name} />
                    )}
                    <AvatarFallback className="text-xs">
                        {getInitials(user.name)}
                    </AvatarFallback>
                </Avatar>
            </div>
        );
    }

    return (
        <div className="group flex items-start gap-3">
            <div className="flex size-7 shrink-0 items-center justify-center rounded-full bg-muted">
                <Bot className="size-4 text-muted-foreground" />
            </div>
            <div className="relative max-w-[80%] rounded-2xl rounded-tl-sm bg-muted px-4 py-2.5">
                <div className="absolute -top-3 -right-1 opacity-0 transition-opacity group-hover:opacity-100">
                    <CopyButton text={content} label="Copy message" />
                </div>
                {toolParts.length > 0 && (
                    <div
                        className="mb-2 flex flex-wrap gap-1.5"
                        role="list"
                        aria-label="Tools used"
                    >
                        {toolParts.map((p, i) => (
                            <span
                                key={p.toolCallId ?? i}
                                role="listitem"
                                className="inline-flex items-center rounded-md border border-border/60 bg-background/60 px-2 py-0.5 text-xs text-muted-foreground"
                                title={`Tool: ${p.toolName ?? 'tool'}`}
                            >
                                {formatToolLabel(p.toolName ?? 'tool')}
                            </span>
                        ))}
                    </div>
                )}
                <div className="prose prose-sm dark:prose-invert max-w-none [&_code]:rounded [&_code]:bg-background/50 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:text-xs [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:bg-background/50 [&_pre]:p-3 [&_pre_code]:bg-transparent [&_pre_code]:p-0">
                    <Markdown
                        remarkPlugins={[remarkGfm]}
                        components={{ pre: CodeBlock }}
                    >
                        {content}
                    </Markdown>
                </div>
            </div>
        </div>
    );
}

export function StreamingIndicator() {
    return (
        <div className="flex items-start gap-3">
            <div className="flex size-7 shrink-0 items-center justify-center rounded-full bg-muted">
                <Bot className="size-4 text-muted-foreground" />
            </div>
            <div className="rounded-2xl rounded-tl-sm bg-muted px-4 py-3">
                <div className="flex items-center gap-1">
                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:-0.3s]" />
                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/60 [animation-delay:-0.15s]" />
                    <span className="size-1.5 animate-bounce rounded-full bg-muted-foreground/60" />
                </div>
            </div>
        </div>
    );
}
