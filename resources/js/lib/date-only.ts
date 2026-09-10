/** YYYY-MM-DD in local time (avoids UTC off-by-one from ISO datetime strings). */
export function toLocalYmd(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
}

/** Format a date-only value for `<input type="date">` or display. */
export function formatDateOnly(value: string): string {
    if (!value) {
        return '';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value.slice(0, 10);
    }

    return toLocalYmd(parsed);
}
