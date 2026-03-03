import { MessageSquare } from 'lucide-react';

export type ChatAgent = 'general' | 'contact' | 'property';

const suggestionsByAgent: Record<ChatAgent, string[]> = {
    contact: [
        'List my 10 most recently updated contacts',
        'Search for contacts by company name',
        'Find contacts interested in SMSF or investment',
        'What do you remember about me?',
    ],
    property: [
        'List recent property reservations',
        'Show me projects (developments)',
        'What lots are available in project X?',
        'Summarize sales this month',
    ],
    general: [
        'What can you help me with?',
        'Explain how this app works',
    ],
};

export function EmptyState({
    agent,
    onSend,
}: {
    agent: ChatAgent;
    onSend: (content: string) => void;
}) {
    const suggestions = suggestionsByAgent[agent];
    return (
        <div className="flex min-h-0 flex-1 items-center justify-center p-4">
            <div className="flex max-w-lg flex-col items-center text-center">
                <div className="mb-4 flex size-12 items-center justify-center rounded-full bg-muted">
                    <MessageSquare className="size-6 text-muted-foreground" />
                </div>
                <h2 className="text-lg font-semibold">Fusion assistant</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                    {agent === 'contact' && 'Search contacts, use RAG over CRM content, or ask me to remember something.'}
                    {agent === 'property' && 'Ask about projects, lots, reservations, and sales (read-only).'}
                    {agent === 'general' && 'General questions and help.'}
                </p>
                <p className="mt-3 text-xs font-medium text-muted-foreground">Try one of these:</p>
                <div className="mt-2 flex flex-wrap justify-center gap-2">
                    {suggestions.map((s) => (
                        <button
                            key={s}
                            type="button"
                            onClick={() => onSend(s)}
                            className="rounded-full border bg-background px-3 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            {s}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}
