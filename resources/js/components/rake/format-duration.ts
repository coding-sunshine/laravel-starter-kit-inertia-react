/**
 * Formats the duration between two ISO timestamps as a human-readable string.
 * Returns null when either input is null/undefined. Negative diffs return "0m".
 *
 * Examples:
 *   formatDuration('2026-05-01T10:00:00Z', '2026-05-01T10:45:00Z') → "45m"
 *   formatDuration('2026-05-01T10:00:00Z', '2026-05-01T13:30:00Z') → "3h 30m"
 *   formatDuration('2026-05-01T10:00:00Z', '2026-05-03T13:00:00Z') → "2d 3h"
 */
export function formatDuration(
    fromIso: string | null | undefined,
    toIso: string | null | undefined,
): string | null {
    if (!fromIso || !toIso) {
        return null;
    }

    const from = new Date(fromIso).getTime();
    const to = new Date(toIso).getTime();
    const diffMs = Math.max(0, to - from);

    const totalMinutes = Math.floor(diffMs / 60_000);
    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes - days * 1440) / 60);
    const minutes = totalMinutes - days * 1440 - hours * 60;

    if (days > 0) {
        return `${days}d ${hours}h`;
    }
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
}
