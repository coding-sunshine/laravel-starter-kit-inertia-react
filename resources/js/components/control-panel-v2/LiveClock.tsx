import { useEffect, useState } from 'react';

export function LiveClock({ className }: { className?: string }) {
    const [now, setNow] = useState<Date>(() => new Date());

    useEffect(() => {
        const id = window.setInterval(() => setNow(new Date()), 1000);
        return () => window.clearInterval(id);
    }, []);

    const time = now.toLocaleTimeString('en-IN', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
    const date = now.toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

    return (
        <div className={className}>
            <div className="text-sm font-semibold tabular-nums text-slate-900">{time}</div>
            <div className="text-xs text-slate-500">{date}</div>
        </div>
    );
}
