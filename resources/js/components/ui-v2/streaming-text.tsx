import { cn } from '@/lib/utils';

export interface StreamingTextProps {
    /** Text rendered so far (token-by-token or chunk-by-chunk). */
    value: string;
    /** Whether the stream is still active (shows blinking cursor). */
    isStreaming?: boolean;
    className?: string;
}

/**
 * Token-by-token / typewriter-style display with optional 2px blinking cursor.
 * Use with SSE/WebSocket for AI responses to lower perceived wait (55–70%).
 */
function StreamingText({
    value,
    isStreaming = false,
    className,
}: StreamingTextProps) {
    return (
        <span
            data-slot="streaming-text"
            className={cn('text-[var(--ai-response-text,inherit)]', className)}
            aria-live="polite"
            aria-busy={isStreaming}
        >
            {value}
            {isStreaming && (
                <span
                    className="ml-0.5 inline-block h-4 w-0.5 animate-pulse bg-current align-middle"
                    aria-hidden
                    style={{ animationDuration: '1s' }}
                />
            )}
        </span>
    );
}

export { StreamingText };
