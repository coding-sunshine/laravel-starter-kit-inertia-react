interface PccStatusPillsProps {
    ok: number;
    near: number;
    over: number;
    empty: number;
}

export function PccStatusPills({ ok, near, over, empty }: PccStatusPillsProps) {
    return (
        <div className="flex flex-wrap items-center gap-2 text-xs font-semibold">
            {ok > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-green-700">
                    ✓ {ok} OK
                </span>
            )}
            {near > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-amber-700">
                    ⚡ {near} Near
                </span>
            )}
            {over > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-red-700">
                    ⚠ {over} Over
                </span>
            )}
            {empty > 0 && (
                <span className="flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-gray-500">
                    ○ {empty} Empty
                </span>
            )}
        </div>
    );
}
