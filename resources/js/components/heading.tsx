import type { ReactNode } from 'react';

export default function Heading({
    title,
    description,
}: {
    title: ReactNode;
    description?: string;
}) {
    return (
        <div className="mb-6 space-y-0.5">
            <h2 className="text-xl font-semibold tracking-tight">{title}</h2>
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
        </div>
    );
}
