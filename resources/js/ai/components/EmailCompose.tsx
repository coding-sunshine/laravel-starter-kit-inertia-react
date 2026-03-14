/**
 * C1 EmailCompose component — renders a pre-filled email draft with Send/Edit/Discard actions.
 */

import type { EmailComposeProps, C1Action } from '../c1-types';

function ActionButton({ action, contactId }: { action: C1Action; contactId?: number }) {
    const base =
        'inline-flex items-center rounded px-3 py-1.5 text-sm font-medium transition-colors';

    if (action.type === 'submit') {
        return (
            <button
                type="button"
                className={`${base} bg-primary text-primary-foreground hover:bg-primary/90`}
            >
                {action.label}
            </button>
        );
    }

    if (action.type === 'dismiss') {
        return (
            <button
                type="button"
                className={`${base} border border-border bg-background hover:bg-muted`}
            >
                {action.label}
            </button>
        );
    }

    return (
        <button
            type="button"
            className={`${base} border border-border bg-background hover:bg-muted`}
        >
            {action.label}
        </button>
    );
}

export function EmailCompose({ to, from, subject, body, contact_id, actions }: EmailComposeProps) {
    return (
        <div className="rounded-lg border border-border bg-card shadow-sm">
            {/* Header */}
            <div className="border-b border-border px-4 py-3">
                <div className="flex items-center gap-2 text-sm">
                    <span className="text-muted-foreground w-8">To</span>
                    <span className="font-medium">{to.name}</span>
                    <span className="text-muted-foreground">&lt;{to.email}&gt;</span>
                </div>
                {from && (
                    <div className="flex items-center gap-2 text-sm">
                        <span className="text-muted-foreground w-8">From</span>
                        <span>{from.name}</span>
                    </div>
                )}
                <div className="flex items-center gap-2 text-sm mt-1">
                    <span className="text-muted-foreground w-8">Re</span>
                    <span className="font-medium">{subject}</span>
                </div>
            </div>

            {/* Body preview */}
            <div className="px-4 py-3">
                <div className="whitespace-pre-wrap text-sm text-foreground">{body}</div>
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2 border-t border-border px-4 py-3">
                {actions.map((action, i) => (
                    <ActionButton key={i} action={action} contactId={contact_id} />
                ))}
            </div>
        </div>
    );
}
