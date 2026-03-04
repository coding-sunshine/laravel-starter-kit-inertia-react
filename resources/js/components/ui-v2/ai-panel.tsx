import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * AI panel container: Glassmorphism 2.0, dark base, 16px radius.
 * Use aria-live="polite" for streaming content so screen readers announce updates.
 */
function AiPanel({
    className,
    'aria-live': ariaLive = 'polite',
    ...props
}: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot="ai-panel"
            role="region"
            aria-label="AI response"
            aria-live={ariaLive}
            className={cn(
                'rounded-[var(--radius-ai-panel,16px)] border [border-color:var(--ai-panel-border)] bg-[var(--ai-panel-bg)] shadow-[var(--ai-panel-shadow)] backdrop-blur-[var(--ai-panel-blur)]',
                'text-[var(--ai-base-fg)]',
                'min-h-[120px]',
                className,
            )}
            {...props}
        />
    );
}

export { AiPanel };
